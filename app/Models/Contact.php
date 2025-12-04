<?php

namespace App\Models;

use App\Observers\ContactObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use MongoDB\BSON\ObjectId;
use MongoDB\Laravel\Eloquent\Model;

#[ObservedBy([ContactObserver::class])]
class Contact extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var string $collection The MongoDB collection name.
     */
    protected string $collection = 'contacts';

    /**
     * @var string $connection The database connection name.
     */
    protected $connection = 'mongodb';

    /**
     * @var string[] $fillable The attributes that are mass assignable.
     */
    protected $fillable = [
        'remote_phone_number',
        'normalized_number',
        'debtor_id',
        'debtor_link_source',
        'debtor_link_last_checked_at',
        'debtor_history',
        'on_whatsapp',
        'number_info',
        'whatsapp_info',
        'unread_messages',
        'last_messages_events',
        'last_message'
    ];

    /**
     * @var string[] $casts The attributes that should be cast to native types.
     */
    protected $casts = [
        // Simple fields
        'remote_phone_number' => 'string',
        'debtor_id' => 'integer',
        'debtor_link_source' => 'string',
        'debtor_link_last_checked_at' => 'datetime',
        'on_whatsapp' => 'boolean',
        'unread_messages' => 'integer',

        // Complex fields
        'normalized_number' => \App\Casts\NormalizedNumberCast::class,
        'debtor_history' => \App\Casts\DebtorHistoryCast::class,
        'number_info' => \App\Casts\NumberInfoCast::class,
        'whatsapp_info' => \App\Casts\WhatsappInfoCast::class,
        'last_messages_events' => \App\Casts\LastMessageEventsCast::class,
        'last_message' => \App\Casts\LastMessageCast::class,
    ];

    /**
     * @var string[] $hidden The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * Get the message ID.
     *
     * @return string
     */
    public function getId(): string
    {
        return (string) $this->_id;
    }

    /**
     * Set the message ID.
     *
     * @param string $value
     * @return void
     */
    public function setId(string $value): void
    {
        $this->_id = new ObjectId($value);
    }

    public function getRemotePhoneNumber(): string
    {
        return $this->remote_phone_number;
    }

    public function setRemotePhoneNumber(string $value): void
    {
        $this->remote_phone_number = $value;
    }

    public function getNormalizedNumber(): \App\ValueObjects\NormalizedNumber
    {
        return $this->normalized_number;
    }

    public function setNormalizedNumber(\App\ValueObjects\NormalizedNumber $value): void
    {
        $this->normalized_number = $value;
    }

    public function getDebtorId(): ?int
    {
        return $this->debtor_id;
    }

    public function setDebtorId(?int $value): void
    {
        $this->debtor_id = $value;
    }

    public function getDebtorLinkSource(): ?string
    {
        return $this->debtor_link_source;
    }

    public function setDebtorLinkSource(?string $value): void
    {
        $this->debtor_link_source = $value;
    }

    public function getDebtorLinkLastCheckedAt(): \DateTime
    {
        return $this->debtor_link_last_checked_at;
    }

    public function setDebtorLinkLastCheckedAt(\DateTime $value): void
    {
        $this->debtor_link_last_checked_at = $value;
    }

    /**
     * @return \App\ValueObjects\DebtorHistoryEntry[]
     */
    public function getDebtorHistory(): array
    {
        return $this->debtor_history;
    }

    public function setDebtorHistory(mixed $value)
    {
        $this->debtor_history = $value;
    }

    public function getOnWhatsapp(): bool
    {
        return $this->on_whatsapp;
    }

    public function setOnWhatsapp(bool $value): void
    {
        $this->on_whatsapp = $value;
    }

    public function getNumberInfo(): \App\ValueObjects\NumberInfo
    {
        return $this->number_info;
    }

    public function setNumberInfo(\App\ValueObjects\NumberInfo $value): void
    {
        $this->number_info = $value;
    }

    public function getWhatsappInfo(): \App\ValueObjects\WhatsappInfo
    {
        return $this->whatsapp_info;
    }

    public function setWhatsappInfo(\App\ValueObjects\WhatsappInfo $value): void
    {
        $this->whatsapp_info = $value;
    }

    public function getUnreadMessages(): int
    {
        return $this->unread_messages;
    }

    public function setUnreadMessages(int $value): void
    {
        $this->unread_messages = $value;
    }

    public function getLastMessagesEvents(): \App\ValueObjects\LastMessageEvent
    {
        return $this->last_messages_events;
    }

    public function setLastMessagesEvents(\App\ValueObjects\LastMessageEvent $value): void
    {
        $this->last_messages_events = $value;
    }

    public function getLastMessage(): \App\ValueObjects\LastMessage
    {
        return $this->last_message;
    }

    public function setLastMessage(\App\ValueObjects\LastMessage $value): void
    {
        $this->last_message = $value;
    }
}
