<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        $data['branch_id'] = $tenant->id;
        
        return $data;
    }
}
