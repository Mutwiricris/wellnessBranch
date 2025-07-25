<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Booking')
                ->modalDescription('Are you sure you want to delete this booking? This action cannot be undone.')
                ->successNotificationTitle('Booking deleted successfully'),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->previous ?? $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure the booking stays within the same branch
        $data['branch_id'] = $this->record->branch_id;
        
        // If end_time is not provided, calculate it based on service duration
        if (!isset($data['end_time']) && isset($data['start_time']) && isset($data['service_id'])) {
            $service = \App\Models\Service::find($data['service_id']);
            if ($service) {
                $startTime = \Carbon\Carbon::parse($data['start_time']);
                $endTime = $startTime->copy()->addMinutes($service->duration ?? 60);
                $data['end_time'] = $endTime->format('H:i');
            }
        }
        
        return $data;
    }
}