<?php

namespace App\Filament\Resources\ClientMembershipResource\Pages;

use App\Filament\Resources\ClientMembershipResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientMemberships extends ListRecords
{
    protected static string $resource = ClientMembershipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
