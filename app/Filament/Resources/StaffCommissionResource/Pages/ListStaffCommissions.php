<?php

namespace App\Filament\Resources\StaffCommissionResource\Pages;

use App\Filament\Resources\StaffCommissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStaffCommissions extends ListRecords
{
    protected static string $resource = StaffCommissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Read-only, no create action
        ];
    }
}
