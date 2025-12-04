<?php

namespace App\Actions;

use App\Exceptions\BadRequestException;
use App\Exceptions\ExternalServiceException;
use App\Libraries\Whatsapp\Client;
use App\Libraries\Whatsapp\Messages\TextMessage;
use App\Models\Message;
use App\ValueObjects\Delivery;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;

class StoreMessageAction
{
    /**
     * Handle storing and sending a message with the client.
     *
     * @throws ConnectionException
     * @throws BadRequestException
     * @throws ExternalServiceException
     */
    public function handle(array $data): Message
    {
        $client = Client::makeByCoordinationId($data['message']['info']['coordination_id']);

        $textMessageToSend = new TextMessage(
            $data['message']['info']['to'],
            $data['message']['data']['text'] ?? null,
            $data['message']['data']['url'] ?? null
        );

        $response = $client->sendMessage($textMessageToSend);

        if (isset($response['status']) && $response['status'] === 'error') {
            throw new ExternalServiceException(
                "Error al enviar el mensaje: " . ($response['message'] ?? 'Desconocido'),
                502,
                '2Chat API'
            );
        }

        $message = new Message();
        $message->setMessageUuid($response['message_uuid']);
        $message->setChannelPhoneNumber($client->phoneNumber());
        $message->setRemotePhoneNumber($data['message']['info']['to']);
        $message->setDirection('outbound');
        $message->setType('text');
        $message->setText($textMessageToSend->text());
        $message->setStatus('sent');
        $message->setSource('aquila');
        $message->setDebtorId($data['message']['info']['debtor_id']);
        $message->setDelivery(new Delivery(
            sentAt: Carbon::now(),
            deliveredAt: null,
            readAt: null
        ));
        $message->save();

        return $message;
    }
}
