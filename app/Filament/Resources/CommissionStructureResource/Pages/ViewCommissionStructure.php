<?php

namespace App\Filament\Resources\CommissionStructureResource\Pages;

use App\Filament\Resources\CommissionStructureResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCommissionStructure extends ViewRecord
{
    protected static string $resource = CommissionStructureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Read-only, no edit or delete actions
        ];
    }
}
