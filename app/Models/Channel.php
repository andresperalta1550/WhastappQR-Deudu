<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use MongoDB\Laravel\Eloquent\Model;

/**
 * Channel model.
 *
 * This model represents a communication channel reference in the
 * system with whatsapp integration. Specifically, it is used to store
 * the channel ID associated with WhatsApp channels.
 */
class Channel extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var string $collection The MongoDB collection associated with the model.
     */
    protected string $collection = 'channels';

    /**
     * @var string $connection The database connection name.
     */
    protected $connection = 'mongodb';

    /**
     * @var string[] $fillable The attributes that are mass assignable.
     */
    protected $fillable = [
        'channel_uuid',
        'friendly_name',
        'phone_number',
        'iso_country_code',
        'pushname',
        'server',
        'platform',
        'connection_status',
        'enabled',
        'is_business_profile',
        'sync_contacts',
        'priority',
        'coordination_id',
        'last_status_event',
        'validator_usage'
    ];

    /**
     * @var string[] $casts The attributes that should be cast to native types.
     */
    protected $casts = [
        // Simple fields
        'channel_uuid' => 'string',
        'friendly_name' => 'string',
        'phone_number' => 'string',
        'iso_country_code' => 'string',
        'pushname' => 'string',
        'server' => 'string',
        'platform' => 'string',
        'connection_status' => 'string',
        'enabled' => 'boolean',
        'is_business_profile' => 'boolean',
        'sync_contacts' => 'boolean',
        'priority' => 'integer',
        'coordination_id' => 'integer',

        // Complex fields
        'last_status_event' => \App\Casts\LastStatusEventCast::class
    ];

    /**
     * @var string[] $appends The attributes that should be appended to the model's JSON form.
     */
    protected $appends = [
        'coordination_fullname'
    ];

    /**
     * @var string[] $hidden The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'coordination',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * Get the channel UUID.
     *
     * @return string|null
     */
    public function getChannelUuid(): ?string
    {
        return $this->channel_uuid;
    }

    /**
     * Set the channel UUID.
     *
     * @param string $uuid
     * @return void
     */
    public function setChannelUuid(string $uuid): void
    {
        $this->channel_uuid = $uuid;
    }

    /**
     * Get the friendly name of the channel.
     *
     * @return string|null
     */
    public function getFriendlyName(): ?string
    {
        return $this->friendly_name;
    }

    /**
     * Set the friendly name of the channel.
     *
     * @param string $name
     * @return void
     */
    public function setFriendlyName(string $name): void
    {
        $this->friendly_name = $name;
    }

    /**
     * Get the phone number associated with the channel.
     *
     * @return string|null
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phone_number;
    }

    /**
     * Set the phone number associated with the channel.
     *
     * @param string $number
     * @return void
     */
    public function setPhoneNumber(string $number): void
    {
        $this->phone_number = $number;
    }

    /**
     * Get the ISO country code associated with the channel.
     *
     * @return string|null
     */
    public function getIsoCountryCode(): ?string
    {
        return $this->iso_country_code;
    }

    /**
     * Set the ISO country code associated with the channel.
     *
     * @param string $code
     * @return void
     */
    public function setIsoCountryCode(string $code): void
    {
        $this->iso_country_code = $code;
    }

    /**
     * Get the pushname associated with the channel.
     *
     * @return string|null
     */
    public function getPushame(): ?string
    {
        return $this->pushname;
    }

    /**
     * Set the pushname associated with the channel.
     *
     * @param string $pushname
     * @return void
     */
    public function setPushname(string $pushname): void
    {
        $this->pushname = $pushname;
    }

    /**
     * Set the server associated with the channel.
     *
     * @return string|null
     */
    public function getServer(): ?string
    {
        return $this->server;
    }

    /**
     * Set the server associated with the channel.
     *
     * @param string $server
     * @return void
     */
    public function setServer(string $server): void
    {
        $this->server = $server;
    }

    /**
     * Get the platform associated with the channel.
     *
     * @return string|null
     */
    public function getPlatform(): ?string
    {
        return $this->platform;
    }

    /**
     * Set the platform associated with the channel.
     *
     * @param string $platform
     * @return void
     */
    public function setPlatform(string $platform): void
    {
        $this->platform = $platform;
    }

    /**
     * Get the connection status of the channel.
     *
     * @return string|null
     */
    public function getConnectionStatus(): ?string
    {
        return $this->connection_status;
    }

    /**
     * Set the connection status of the channel.
     *
     * @param string $status
     * @return void
     */
    public function setConnectionStatus(string $status): void
    {
        $this->connection_status = $status;
    }

    /**
     * Get whether the channel is enabled.
     *
     * @return bool|null
     */
    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    /**
     * Set whether the channel is enabled.
     *
     * @param bool $enabled
     * @return void
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * Get whether the channel is a business profile.
     *
     * @return bool|null
     */
    public function isBusinessProfile(): ?bool
    {
        return $this->is_business_profile;
    }

    /**
     * Set whether the channel is a business profile.
     *
     * @param bool $isBusiness
     * @return void
     */
    public function setIsBusinessProfile(bool $isBusiness): void
    {
        $this->is_business_profile = $isBusiness;
    }

    /**
     * Get whether to sync contacts.
     *
     * @return bool|null
     */
    public function syncContacts(): ?bool
    {
        return $this->sync_contacts;
    }

    /**
     * Set whether to sync contacts.
     *
     * @param bool $sync
     * @return void
     */
    public function setSyncContacts(bool $sync): void
    {
        $this->sync_contacts = $sync;
    }

    /**
     * Get the priority of the channel.
     *
     * @return int|null
     */
    public function getPriority(): ?int
    {
        return $this->priority;
    }

    /**
     * Set the priority of the channel.
     *
     * @param int $priority
     * @return void
     */
    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    /**
     * Get the coordination ID of the channel.
     *
     * @return int|null
     */
    public function getCoordinationId(): ?int
    {
        return $this->coordination_id;
    }

    /**
     * Set the coordination ID of the channel.
     *
     * @param int $id
     * @return void
     */
    public function setCoordinationId(int $id): void
    {
        $this->coordination_id = $id;
    }

    /**
     * Get the last status event of the channel.
     *
     * @return \App\ValueObjects\LastStatusEvent|null
     */
    public function getLastStatusEvent(): ?\App\ValueObjects\LastStatusEvent
    {
        return $this->last_status_event;
    }

    /**
     * Set the last status event of the channel.
     *
     * @param \App\ValueObjects\LastStatusEvent $event
     * @return void
     */
    public function setLastStatusEvent(\App\ValueObjects\LastStatusEvent $event): void
    {
        $this->last_status_event = $event;
    }

    /**
     * Get the coordination fullname of the channel.
     *
     * @return string
     */
    public function getCoordinationFullnameAttribute(): ?string
    {
        $fullname = $this->coordination?->name;
        $fullname .= ' ' . $this->coordination?->lastname;
        return $fullname;
    }

    /**
     * Relationship with coordination
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function coordination(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'coordination_id', 'id');
    }

    public static function getChannelByPhoneNumber(string $phoneNumber): ?self
    {
        return self::where('phone_number', $phoneNumber)->first();
    }

    /**
     * Get the validator usage by coordination.
     *
     * @param int $coordinationId
     * @return int
     */
    public static function validatorUsageByCoordination(int $coordinationId): int
    {
        return self::where('coordination_id', $coordinationId)
            ->sum('validator_usage') ?? 0;
    }
}
