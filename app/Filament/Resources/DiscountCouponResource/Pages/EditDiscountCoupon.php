<?php

namespace App\Filament\Resources\DiscountCouponResource\Pages;

use App\Filament\Resources\DiscountCouponResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDiscountCoupon extends EditRecord
{
    protected static string $resource = DiscountCouponResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}