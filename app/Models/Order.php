<?php

namespace App\Models;

use App\Enums\OrderRecipientType;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'client_id', 'institution_id', 'source', 'recipient_type', 'status',
    'total_bonus', 'total_weight_grams', 'reserved_bonus_amount',
    'placed_at', 'status_changed_at', 'delivered_at', 'cancelled_at',
    'created_by_user_id', 'recipient_first_name', 'recipient_last_name', 'recipient_bin',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => OrderSource::class,
            'recipient_type' => OrderRecipientType::class,
            'status' => OrderStatus::class,
            'total_bonus' => 'integer',
            'total_weight_grams' => 'integer',
            'reserved_bonus_amount' => 'integer',
            'placed_at' => 'datetime',
            'status_changed_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsTo<Institution, $this> */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasMany<OrderStatusHistory, $this> */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    /** @return HasMany<BonusTransaction, $this> */
    public function bonusTransactions(): HasMany
    {
        return $this->hasMany(BonusTransaction::class);
    }

    public function canBeDeleted(): bool
    {
        return $this->status === OrderStatus::Cancelled;
    }

    public function getRecipientFullNameAttribute(): string
    {
        $firstName = $this->recipient_first_name ?: $this->client?->first_name;
        $lastName = $this->recipient_last_name ?: $this->client?->last_name;

        return trim(implode(' ', array_filter([$firstName, $lastName])));
    }

    public function getRecipientBinValueAttribute(): ?string
    {
        return $this->recipient_bin ?: $this->client?->bin;
    }
}
