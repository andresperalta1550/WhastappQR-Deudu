<?php

namespace App\Libraries\Whatsapp\Messages;

use JsonSerializable;
abstract class WhatsappMessage implements JsonSerializable
{
    /**
     * @var string $to The recipient's phone number.
     * @example +573223032239
     */
    protected string $to;

    public function __construct(string $to)
    {
        $this->to = $to;
    }

    /**
     * Serialize the message data.
     *
     * @return array The message data.
     */
    public function jsonSerialize(): array
    {
        return [
            'to_number' => $this->to
        ];
    }

    /**
     * Build the complete payload with the sender's phone number.
     *
     * @param string $fromNumber The sender's phone number
     * @returns array The message data.
     */
    public function toPayload(string $fromNumber): array
    {
        $data = $this->jsonSerialize();
        $data['from_number'] = $fromNumber;
        return $data;
    }
}
