<?php

namespace App\Support\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case MPESA = 'mpesa';
    case CARD = 'card';
    case BANK_TRANSFER = 'bank_transfer';
    case GIFT_VOUCHER = 'gift_voucher';
    case LOYALTY_POINTS = 'loyalty_points';
    case MIXED = 'mixed';

    /**
     * Get label for display
     */
    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::MPESA => 'M-Pesa',
            self::CARD => 'Card',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::GIFT_VOUCHER => 'Gift Voucher',
            self::LOYALTY_POINTS => 'Loyalty Points',
            self::MIXED => 'Mixed Payment',
        };
    }

    /**
     * Get icon for Filament
     */
    public function icon(): string
    {
        return match ($this) {
            self::CASH => 'heroicon-o-banknotes',
            self::MPESA => 'heroicon-o-device-phone-mobile',
            self::CARD => 'heroicon-o-credit-card',
            self::BANK_TRANSFER => 'heroicon-o-building-library',
            self::GIFT_VOUCHER => 'heroicon-o-gift',
            self::LOYALTY_POINTS => 'heroicon-o-star',
            self::MIXED => 'heroicon-o-arrows-right-left',
        };
    }

    /**
     * Get color for Filament
     */
    public function color(): string
    {
        return match ($this) {
            self::CASH => 'success',
            self::MPESA => 'warning',
            self::CARD => 'info',
            self::BANK_TRANSFER => 'primary',
            self::GIFT_VOUCHER => 'pink',
            self::LOYALTY_POINTS => 'amber',
            self::MIXED => 'purple',
        };
    }

    /**
     * Check if method requires online processing
     */
    public function requiresOnlineProcessing(): bool
    {
        return in_array($this, [self::MPESA, self::CARD, self::BANK_TRANSFER]);
    }

    /**
     * Check if method requires transaction reference
     */
    public function requiresTransactionReference(): bool
    {
        return in_array($this, [self::MPESA, self::CARD, self::BANK_TRANSFER]);
    }

    /**
     * Check if method is instant
     */
    public function isInstant(): bool
    {
        return in_array($this, [self::CASH, self::GIFT_VOUCHER, self::LOYALTY_POINTS]);
    }

    /**
     * Get processing fee percentage
     */
    public function processingFeePercentage(): float
    {
        return match ($this) {
            self::MPESA => 0.015, // 1.5%
            self::CARD => 0.025, // 2.5%
            self::BANK_TRANSFER => 0.005, // 0.5%
            default => 0.0,
        };
    }

    /**
     * Get all values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get options for select dropdown
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($method) => [$method->value => $method->label()])
            ->toArray();
    }
}
