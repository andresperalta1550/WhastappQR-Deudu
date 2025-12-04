<?php

namespace App\Libraries\Whatsapp\Messages;

use App\Libraries\Whatsapp\Messages\WhatsappMessage;

/**
 * This class provides the basic structure and functionality for a Whatsapp message.
 */
class TextMessage extends WhatsappMessage
{
    /**
     * @var string $text The text content of the message.
     */
    private ?string $text;

    /**
     * @var string|null $url The url to preview.
     */
    private ?string $url;

    public function __construct(
        string $to,
        ?string $text,
        ?string $url,
    ) {
        parent::__construct($to);
        $this->text = $text;
        $this->url = $url;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();

        $data['text'] = $this->text;

        if ($this->url !== null) {
            $data['url'] = $this->url;
        }

        return $data;
    }

    /**
     * Get the text content of the message.
     *
     * @return string The text content of the message.
     */
    public function text(): ?string
    {
        return $this->text;
    }

    /**
     * Get the url content of the message.
     *
     * @return string|null The url content of the message.
     */
    public function url(): ?string
    {
        return $this->url;
    }
}
