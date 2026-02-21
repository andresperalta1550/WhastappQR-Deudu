<?php

namespace App\Jobs;

use App\Libraries\DebtorFallbackResolver;
use App\Libraries\Whatsapp\Client as WhatsappClient;
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
use libphonenumber\NumberParseException;

/**
 * Job to resolve the contact associated with an outgoing message.
 * This job checks if the contact exists, creates it if not, and attempts to
 * link it to a debtor using the DebtorFallbackResolver.
 *
 * If the contact already has a debtor_id assigned, this job will skip
 * the resolution process and only set the debtor_id in the message.
 */
class ResolveOutgoingMessageContactJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Message $message;

    public function __construct(Message $message)
    {
        Log::debug("[ResolveOutgoingMessageContactJob] Job created", [
            'message_uuid' => $message->getMessageUuid(),
            'remote_phone_number' => $message->getRemotePhoneNumber(),
            'direction' => $message->getDirection(),
        ]);
        $this->message = $message;
    }

    /**
     * @throws ConnectionException
     */
    public function handle(DebtorFallbackResolver $resolver): void
    {
        Log::debug("[ResolveOutgoingMessageContactJob] Starting handle()");

        // Search for an existing contact
        Log::debug("[ResolveOutgoingMessageContactJob] Searching for existing contact", [
            'remote_phone_number' => $this->message->getRemotePhoneNumber()
        ]);

        $contact = (new Contact())
            ->where('remote_phone_number', $this->message->getRemotePhoneNumber())
            ->first();

        // If not found, create it
        if ($contact) {
            Log::debug("[ResolveOutgoingMessageContactJob] Contact found", [
                'contact_id' => $contact->id,
                'debtor_id' => $contact->getDebtorId()
            ]);
        } else {
            Log::debug("[ResolveOutgoingMessageContactJob] Contact not found, creating new contact...");
            $contact = $this->createContactIfNotExists();
            Log::debug("[ResolveOutgoingMessageContactJob] Contact created", [
                'contact_id' => $contact?->getId()
            ]);
        }

        // If contact already has debtor assigned, skip resolution
        if ($contact->getDebtorId() !== null) {
            $this->setDebtorIdInMessage($contact);

            Log::debug("[ResolveOutgoingMessageContactJob] Contact already has debtor assigned, skipping", [
                'contact_id' => $contact->id,
                'debtor_id' => $contact->getDebtorId()
            ]);
            return;
        }

        // Resolve debtor
        Log::debug("[ResolveOutgoingMessageContactJob] Resolving debtor for phone", [
            'phone' => $this->message->getRemotePhoneNumber()
        ]);

        $phone = $this->getNationalNumberFromInternational(
            $this->message->getRemotePhoneNumber(),
            $contact->getNumberInfo()->getIsoCountryCode()
        );
        $debtorData = $resolver->resolve($phone, $this->message->getMessageUuid());

        Log::debug("[ResolveOutgoingMessageContactJob] Debtor resolver response", [
            'response' => $debtorData
        ]);

        if ($debtorData) {
            Log::info("[ResolveOutgoingMessageContactJob] Debtor found and assigning to contact", [
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

            Log::debug("[ResolveOutgoingMessageContactJob] Contact updated with debtor");

            return;
        }

        Log::info("[ResolveOutgoingMessageContactJob] No debtor found for phone", [
            'remote_phone_number' => $this->message->getRemotePhoneNumber()
        ]);

        $contact->setDebtorId(null);
        $contact->setDebtorLinkSource(null);
        $contact->setDebtorLinkLastCheckedAt(now());
        $contact->save();

        $this->setDebtorIdInMessage($contact);

        Log::debug("[ResolveOutgoingMessageContactJob] Contact saved with null debtor", [
            'contact_id' => $contact->getId()
        ]);
    }

    /**
     * @throws ConnectionException
     */
    private function createContactIfNotExists(): Contact
    {
        Log::debug("[ResolveOutgoingMessageContactJob] Entering createContactIfNotExists()");

        try {
            $whatsappClient = new WhatsappClient();

            Log::debug("[ResolveOutgoingMessageContactJob] Calling WhatsApp API checkNumber()", [
                'phone' => $this->message->getRemotePhoneNumber()
            ]);

            $responseCheckNumber = $whatsappClient->checkNumber(
                $this->message->getRemotePhoneNumber()
            );

            Log::debug("[ResolveOutgoingMessageContactJob] WhatsApp API Response", [
                'response' => $responseCheckNumber
            ]);

            $contact = new Contact();

            $contact->setRemotePhoneNumber($this->message->getRemotePhoneNumber());
            $contact->setChannelPhoneNumber($this->message->getChannelPhoneNumber());
            $contact->setIsResolved(false);

            $contact->setOnWhatsapp($responseCheckNumber['on_whatsapp'] ?? false);

            $numberInfo = new NumberInfo(
                $responseCheckNumber['number']['iso_country_code'] ?? null,
                $responseCheckNumber['number']['carrier'] ?? null,
                $responseCheckNumber['number']['timezone'][0] ?? null,
            );

            Log::debug("[ResolveOutgoingMessageContactJob] NumberInfo created", [
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

            Log::debug("[ResolveOutgoingMessageContactJob] WhatsappInfo created", [
                'whatsapp_info' => $whatsappInfo
            ]);

            $contact->setWhatsappInfo($whatsappInfo);

            // Outbound message events
            Log::debug("[ResolveOutgoingMessageContactJob] Setting outbound message event data");

            $contact->setUnreadMessages(0);
            $contact->setLastMessagesEvents(new LastMessageEvent(
                lastInboundAt: null,
                lastOutboundAt: now(),
                lastCheckNumberAt: now()
            ));

            $lastMessage = new LastMessage(
                direction: 'outbound',
                type: $this->message->getType(),
                text: $this->message->getText(),
                status: 'sent'
            );

            Log::debug("[ResolveOutgoingMessageContactJob] LastMessage created", [
                'last_message' => $lastMessage
            ]);

            $contact->setLastMessage($lastMessage);

            $contact->save();

            Log::info("[ResolveOutgoingMessageContactJob] New contact created", [
                'contact_id' => $contact->id,
                'remote_phone' => $contact->remote_phone_number
            ]);

            return $contact;
        } catch (\Exception $e) {
            Log::error("[ResolveOutgoingMessageContactJob] Error creating contact", [
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
            Log::error("[ResolveOutgoingMessageContactJob] Error parsing phone number", [
                'phone' => $phone,
                'iso' => $iso,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function setDebtorIdInMessage(Contact $contact): void
    {
        Log::debug("[ResolveOutgoingMessageContactJob] Setting debtor_id in message", [
            'message_uuid' => $this->message->getMessageUuid(),
            'debtor_id' => $contact->getDebtorId()
        ]);

        $this->message->setDebtorId($contact->getDebtorId());
        $this->message->save();

        Log::debug("[ResolveOutgoingMessageContactJob] Debtor ID set in message", [
            'debtor_id' => $contact->getDebtorId()
        ]);
    }
}
