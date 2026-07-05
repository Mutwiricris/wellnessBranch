<?php

namespace App\Filament\Resources\CommissionStructureResource\Pages;

use App\Filament\Resources\CommissionStructureResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCommissionStructures extends ListRecords
{
    protected static string $resource = CommissionStructureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Read-only, no create action
        ];
    }
}
