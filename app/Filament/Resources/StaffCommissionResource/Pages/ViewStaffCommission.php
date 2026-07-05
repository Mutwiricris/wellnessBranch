<?php

namespace App\Filament\Resources\StaffCommissionResource\Pages;

use App\Filament\Resources\StaffCommissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewStaffCommission extends ViewRecord
{
    protected static string $resource = StaffCommissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Read-only, no edit or delete actions
        ];
    }
}
