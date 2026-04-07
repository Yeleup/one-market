<?php

namespace App\Models;

use App\Enums\RecipientType;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable([
    'first_name',
    'last_name',
    'bin',
    'login',
    'password',
    'recipient_type',
    'recipient_first_name',
    'recipient_last_name',
    'recipient_bin',
    'bonus_balance',
    'bonus_reserved',
    'is_active',
])]
#[Hidden(['password'])]
class Client extends Authenticatable
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'recipient_type' => RecipientType::class,
            'bonus_balance' => 'integer',
            'bonus_reserved' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getRecipientFullNameAttribute(): string
    {
        if ($this->recipient_type === RecipientType::Other) {
            return trim(implode(' ', array_filter([$this->recipient_first_name, $this->recipient_last_name])));
        }

        return $this->full_name;
    }

    public function getRecipientBinValueAttribute(): ?string
    {
        if ($this->recipient_type === RecipientType::Other) {
            return $this->recipient_bin;
        }

        return $this->bin;
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** @return HasMany<BonusTransaction, $this> */
    public function bonusTransactions(): HasMany
    {
        return $this->hasMany(BonusTransaction::class);
    }
}
