<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use MongoDB\Laravel\Eloquent\Model;

/**
 * The ChannelStatusEvent model represents events related to the status of a communication channel.
 *
 * @package App\Models
 */
class ChannelStatusEvent extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var string $collection The collection associated with the model.
     */
    protected string $collection = 'channel_status_events';

    /**
     * @var string The database connection used by the model.
     */
    protected $connection = 'mongodb';

    /**
     * @var string[] $fillable The attributes that are mass assignable.
     */
    protected $fillable = [
        'channel_uuid',
        'event',
        'qr_code',
        'payload'
    ];

    /**
     * @var string[] $casts The attributes that should be cast to native types.
     */
    protected $casts = [
        // Simple fields
        'channel_uuid' => 'string',
        'event' => 'string',
        'qr_code' => 'string',
        'payload' => 'array',
    ];

    /**
     * @var string[] $hidden The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the channel UUID.
     *
     * @return string
     */
    public function channelUuid(): string
    {
        return $this->channel_uuid;
    }

    /**
     * Set the channel UUID.
     *
     * @param string $channelUuid
     * @return void
     */
    public function setChannelUuid(string $channelUuid): void
    {
        $this->channel_uuid = $channelUuid;
    }

    /**
     * Get the event type.
     *
     * @return string
     */
    public function event(): string
    {
        return $this->event;
    }

    /**
     * Set the event type.
     *
     * @param string $event
     * @return void
     */
    public function setEvent(string $event): void
    {
        $this->event = $event;
    }

    /**
     * Get the QR code.
     *
     * @return string|null
     */
    public function qrCode(): ?string
    {
        return $this->qr_code;
    }

    /**
     * Set the QR code.
     *
     * @param string|null $qrCode
     * @return void
     */
    public function setQrCode(?string $qrCode): void
    {
        $this->qr_code = $qrCode;
    }

    /**
     * Get the payload.
     *
     * @return array
     */
    public function payload(): array
    {
        return $this->payload;
    }

    /**
     * Set the payload.
     *
     * @param array $payload
     * @return void
     */
    public function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }
}
