<?php

namespace App\Jobs;

use App\Libraries\DebtorFallbackResolver;
use App\Libraries\Whatsapp\Client as WhatsappClient;
use App\Libraries\Whatsapp\Webhook\Events\MessageEvent;
use App\Models\Contact;
use App\Models\Message;
use App\ValueObjects\LastMessage;
use App\ValueObjects\LastMessageEvent;
use App\ValueObjects\NormalizedNumber;
use App\ValueObjects\NumberInfo;
use App\ValueObjects\WhatsappInfo;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\NumberParseException;

/**
 * Job to resolve the contact associated with an incoming WhatsApp message.
 * This job checks if the contact exists, creates it if not, and attempts to
 * link it to a debtor using the DebtorFallbackResolver.
 */
class ResolveIncomingMessageContactJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected MessageEvent $event;

    public function __construct(MessageEvent $event)
    {
        Log::debug("[ResolveIncomingMessageContactJob] Job created", [
            'remote_phone_number' => $event->remotePhoneNumber,
            'sent_by' => $event->sentBy,
            'event_type' => $event->eventType,
            'timestamp' => $event->timestamp
        ]);
        $this->event = $event;
    }

    /**
     * @throws ConnectionException
     */
    public function handle(DebtorFallbackResolver $resolver): void
    {
        Log::debug("[ResolveIncomingMessageContactJob] Starting handle()");

        // Search a contact
        Log::debug("[ResolveIncomingMessageContactJob] Searching for existing contact", [
            'remote_phone_number' => $this->event->remotePhoneNumber
        ]);

        $contact = (new Contact())
            ->where('remote_phone_number', $this->event->remotePhoneNumber)
            ->first();

        // If not found, create it
        if ($contact) {
            Log::debug("[ResolveIncomingMessageContactJob] Contact found", [
                'contact_id' => $contact->id,
                'debtor_id' => $contact->getDebtorId()
            ]);
        } else {
            Log::debug("[ResolveIncomingMessageContactJob] Contact not found, creating new contact...");
            $contact = $this->createContactIfNotExists();
            Log::debug("[ResolveIncomingMessageContactJob] Contact created", [
                'contact_id' => $contact?->getId()
            ]);
        }

        // If contact already has debtor assigned, skip
        if ($contact->getDebtorId() !== null) {
            $this->setDebtorIdInMessage($contact);

            Log::debug("[ResolveIncomingMessageContactJob] Contact already has debtor assigned, skipping", [
                'contact_id' => $contact->id,
                'debtor_id' => $contact->getDebtorId()
            ]);
            return;
        }

        // Resolver debtor
        Log::debug("[ResolveIncomingMessageContactJob] Resolving debtor for phone", [
            'phone' => $this->event->remotePhoneNumber
        ]);

        $phone = $this->getNationalNumberFromInternational(
            $this->event->remotePhoneNumber,
            $contact->getNumberInfo()->getIsoCountryCode()
        );
        $debtorData = $resolver->resolve($phone, $this->event->uuid);

        Log::debug("[ResolveIncomingMessageContactJob] Debtor resolver response", [
            'response' => $debtorData
        ]);

        if ($debtorData) {
            Log::info("[ResolveIncomingMessageContactJob] Debtor found and assigning to contact", [
                'contact_id' => $contact->getId(),
                'debtor_id' => $debtorData['debtor_id'],
                'source' => $debtorData['source']
            ]);

            $normalizedNumber = new NormalizedNumber(
                countryCode: $debtorData['country_code'],
                cityCode: $debtorData['city_code'],
                number: $phone
            );

            $contact->setDebtorId($debtorData['debtor_id']);
            $contact->setDebtorLinkSource($debtorData['source']);
            $contact->setDebtorLinkLastCheckedAt(now());
            $contact->setNormalizedNumber($normalizedNumber);
            $contact->save();

            $this->setDebtorIdInMessage($contact);

            Log::debug("[ResolveIncomingMessageContactJob] Contact updated with debtor");

            return;
        }

        Log::info("[ResolveIncomingMessageContactJob] No debtor found for phone", [
            'remote_phone_number' => $this->event->remotePhoneNumber
        ]);

        $contact->setDebtorId(null);
        $contact->setDebtorLinkSource(null);
        $contact->setDebtorLinkLastCheckedAt(now());
        $contact->save();

        $this->setDebtorIdInMessage($contact);

        Log::debug("[ResolveIncomingMessageContactJob] Contact saved with null debtor", [
            'contact_id' => $contact->getId()
        ]);
    }

    /**
     * @throws ConnectionException
     */
    private function createContactIfNotExists(): Contact
    {
        Log::debug("[ResolveIncomingMessageContactJob] Entering createContactIfNotExists()");

        try {
            $whatsappClient = new WhatsappClient();

            Log::debug("[ResolveIncomingMessageContactJob] Calling WhatsApp API checkNumber()", [
                'phone' => $this->event->remotePhoneNumber
            ]);

            $responseCheckNumber = $whatsappClient->checkNumber(
                $this->event->remotePhoneNumber
            );

            Log::debug("[ResolveIncomingMessageContactJob] WhatsApp API Response", [
                'response' => $responseCheckNumber
            ]);

            $contact = new Contact();

            $contact->setRemotePhoneNumber($this->event->remotePhoneNumber);
            $contact->setChannelPhoneNumber($this->event->channelPhoneNumber);

            $contact->setOnWhatsapp($responseCheckNumber['on_whatsapp'] ?? false);

            $numberInfo = new NumberInfo(
                $responseCheckNumber['number']['iso_country_code'] ?? null,
                $responseCheckNumber['number']['carrier'] ?? null,
                $responseCheckNumber['number']['timezone'][0] ?? null,
            );

            Log::debug("[ResolveIncomingMessageContactJob] NumberInfo created", [
                'number_info' => $numberInfo
            ]);

            $contact->setNumberInfo($numberInfo);

            $whatsappInfo = new WhatsappInfo(
                $responseCheckNumber['whatsapp_info']['number_id'] ?? null,
                $responseCheckNumber['whatsapp_info']['is_business'] ?? null,
                $responseCheckNumber['whatsapp_info']['is_enterprise'] ?? null,
                $responseCheckNumber['whatsapp_info']['verified_level'] ?? null,
                $responseCheckNumber['whatsapp_info']['verified_name'] ?? null,
                $responseCheckNumber['whatsapp_info']['status_text'] ?? null
            );

            Log::debug("[ResolveIncomingMessageContactJob] WhatsappInfo created", [
                'whatsapp_info' => $whatsappInfo
            ]);

            $contact->setWhatsappInfo($whatsappInfo);

            // Eventos
            Log::debug("[ResolveIncomingMessageContactJob] Setting message event data");

            if ($this->event->sentBy === 'user') {
                Log::debug("[ResolveIncomingMessageContactJob] Message sent by user (inbound)");

                $contact->setUnreadMessages(1);
                $contact->setLastMessagesEvents(new LastMessageEvent(
                    lastInboundAt: $this->event->timestamp
                    ? \Carbon\Carbon::parse($this->event->timestamp)
                    : null,
                    lastOutboundAt: null,
                    lastCheckNumberAt: now()
                ));
            } else {
                Log::debug("[ResolveIncomingMessageContactJob] Message sent by system (outbound)");

                $contact->setUnreadMessages(0);
                $contact->setLastMessagesEvents(new LastMessageEvent(
                    lastInboundAt: null,
                    lastOutboundAt: $this->event->timestamp
                    ? \Carbon\Carbon::parse($this->event->timestamp)
                    : null,
                    lastCheckNumberAt: now()
                ));
            }

            $lastMessage = new LastMessage(
                direction: $this->event->sentBy === 'user' ? 'inbound' : 'outbound',
                type: $this->event->eventType,
                text: $this->event->payload['message']['text'] ?? null,
                status: 'delivered'
            );

            Log::debug("[ResolveIncomingMessageContactJob] LastMessage created", [
                'last_message' => $lastMessage
            ]);

            $contact->setLastMessage($lastMessage);

            $contact->save();

            Log::info("[ResolveIncomingMessageContactJob] New contact created", [
                'contact_id' => $contact->id,
                'remote_phone' => $contact->remote_phone_number
            ]);

            return $contact;
        } catch (\Exception $e) {
            Log::error("[ResolveIncomingMessageContactJob] Error creating contact", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Parse the international phone number and extract the national number.
     *
     * @param string $phone International phone number in E.164 format
     * @param string|null $iso ISO country code (e.g., 'US', 'CO')
     * @return string|null National number without country code, or null if parsing fails
     */
    private function getNationalNumberFromInternational(string $phone, ?string $iso): ?string
    {
        try {
            $phoneUtil = PhoneNumberUtil::getInstance();

            // Parse the phone number using the provided ISO country code
            $parsed = $phoneUtil->parse($phone, $iso ?? 'CO');

            // Extract the national number (without prefix)
            $national = $parsed->getNationalNumber();

            return (string) $national;
        } catch (NumberParseException $e) {
            Log::error("[ResolveIncomingMessageContactJob] Error parsing phone number", [
                'phone' => $phone,
                'iso' => $iso,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function setDebtorIdInMessage(Contact $contact): void
    {
        Log::debug("[ResolveIncomingMessageContactJob] Entering setDebtorIdInMessage()");

        $message = (new Message())
            ->where('message_uuid', $this->event->uuid)
            ->first();

        if ($message === null) {
            Log::debug("[ResolveIncomingMessageContactJob] Message not found for UUID", [
                'uuid' => $this->event->uuid
            ]);
            return;
        }

        $message->setDebtorId($contact->getDebtorId());
        $message->save();

        Log::debug("[ResolveIncomingMessageContactJob] Debtor ID set in message", [
            'debtor_id' => $contact->getDebtorId()
        ]);
    }
}
