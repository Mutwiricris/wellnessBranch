<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('start_service')
                ->label('Start Service')
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn (): bool => $this->record->canBeStarted())
                ->requiresConfirmation()
                ->modalHeading('Start Service')
                ->modalDescription('Are you sure you want to start this service? The booking status will be updated to "In Progress".')
                ->action(function () {
                    $this->record->updateStatusWithPayment('in_progress');
                    $this->refreshFormData([
                        'status',
                    ]);
                })
                ->after(function () {
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),
                
            Actions\Action::make('complete_service')
                ->label('Complete Service')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn (): bool => $this->record->canBeCompleted())
                ->requiresConfirmation()
                ->modalHeading('Complete Service')
                ->modalDescription('Are you sure you want to complete this service? The booking will be marked as completed.')
                ->action(function () {
                    $this->record->updateStatusWithPayment('completed');
                    $this->refreshFormData([
                        'status', 'payment_status'
                    ]);
                })
                ->after(function () {
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                }),
                
            Actions\Action::make('record_payment')
                ->label('Record Payment')
                ->icon('heroicon-o-credit-card')
                ->color('warning')
                ->visible(fn (): bool => $this->record->canEditPayment())
                ->form([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('amount')
                                ->label('Amount')
                                ->numeric()
                                ->prefix('KES')
                                ->step(0.01)
                                ->default($this->record->total_amount)
                                ->required(),
                                
                            Forms\Components\Select::make('payment_method')
                                ->label('Payment Method')
                                ->options([
                                    'cash' => 'Cash',
                                    'mpesa' => 'M-Pesa',
                                    'card' => 'Card',
                                    'bank_transfer' => 'Bank Transfer'
                                ])
                                ->default($this->record->payment_method)
                                ->required()
                                ->native(false),
                                
                            Forms\Components\TextInput::make('transaction_reference')
                                ->label('Transaction Reference')
                                ->maxLength(100)
                                ->helperText('For M-Pesa or card payments'),
                                
                            Forms\Components\Select::make('payment_status')
                                ->label('Payment Status')
                                ->options([
                                    'pending' => 'Pending',
                                    'completed' => 'Completed',
                                    'failed' => 'Failed'
                                ])
                                ->default('completed')
                                ->required()
                                ->native(false),
                        ])
                ])
                ->action(function (array $data) {
                    $tenant = \Filament\Facades\Filament::getTenant();
                    
                    // Create or update payment record
                    $payment = \App\Models\Payment::updateOrCreate(
                        ['booking_id' => $this->record->id],
                        [
                            'branch_id' => $tenant->id,
                            'amount' => $data['amount'],
                            'payment_method' => $data['payment_method'],
                            'transaction_reference' => $data['transaction_reference'] ?? null,
                            'status' => $data['payment_status'],
                            'processed_at' => $data['payment_status'] === 'completed' ? now() : null,
                        ]
                    );

                    // Update booking payment information
                    $this->record->update([
                        'payment_status' => $data['payment_status'],
                        'payment_method' => $data['payment_method'],
                        'total_amount' => $data['amount'],
                    ]);
                    
                    $this->refreshFormData([
                        'payment_status', 'payment_method', 'total_amount'
                    ]);
                })
                ->successNotificationTitle('Payment recorded successfully'),
                
            Actions\Action::make('assign_staff')
                ->label('Assign Staff')
                ->icon('heroicon-o-user-plus')
                ->color('info')
                ->visible(fn (): bool => !$this->record->staff_id || $this->record->status === 'pending')
                ->form([
                    Forms\Components\Select::make('staff_id')
                        ->label('Staff Member')
                        ->options(function () {
                            $tenant = \Filament\Facades\Filament::getTenant();
                            return \App\Models\Staff::where('status', 'active')
                                ->whereHas('branches', function ($query) use ($tenant) {
                                    $query->where('branch_id', $tenant->id);
                                })
                                ->whereHas('services', function ($query) {
                                    $query->where('service_id', $this->record->service_id);
                                })
                                ->pluck('name', 'id');
                        })
                        ->required()
                        ->native(false)
                        ->searchable(),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'staff_id' => $data['staff_id']
                    ]);
                    
                    $this->refreshFormData(['staff_id']);
                })
                ->successNotificationTitle('Staff assigned successfully'),
                
            Actions\Action::make('cancel_booking')
                ->label('Cancel Booking')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->visible(fn (): bool => $this->record->canBeCancelled())
                ->form([
                    Forms\Components\Textarea::make('cancellation_reason')
                        ->label('Cancellation Reason')
                        ->required()
                        ->rows(3)
                        ->placeholder('Please provide a reason for cancellation...'),
                ])
                ->requiresConfirmation()
                ->modalHeading('Cancel Booking')
                ->modalDescription('Are you sure you want to cancel this booking? This action cannot be undone.')
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => 'cancelled',
                        'cancellation_reason' => $data['cancellation_reason'],
                        'cancelled_at' => now(),
                    ]);
                    
                    $this->refreshFormData([
                        'status', 'cancellation_reason', 'cancelled_at'
                    ]);
                })
                ->successNotificationTitle('Booking cancelled successfully'),
                
            Actions\Action::make('reschedule')
                ->label('Reschedule')
                ->icon('heroicon-o-calendar')
                ->color('warning')
                ->visible(fn (): bool => in_array($this->record->status, ['pending', 'confirmed']))
                ->form([
                    Forms\Components\DatePicker::make('new_date')
                        ->label('New Date')
                        ->required()
                        ->minDate(now())
                        ->default($this->record->appointment_date),
                        
                    Forms\Components\TimePicker::make('new_start_time')
                        ->label('New Start Time')
                        ->required()
                        ->default($this->record->start_time),
                        
                    Forms\Components\TimePicker::make('new_end_time')
                        ->label('New End Time')
                        ->required()
                        ->default($this->record->end_time)
                        ->afterOrEqual('new_start_time'),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'appointment_date' => $data['new_date'],
                        'start_time' => $data['new_start_time'],
                        'end_time' => $data['new_end_time'],
                    ]);
                    
                    $this->refreshFormData([
                        'appointment_date', 'start_time', 'end_time'
                    ]);
                })
                ->successNotificationTitle('Booking rescheduled successfully'),
                
            Actions\EditAction::make(),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            // Add any widgets you want to show at the top of the view page
        ];
    }
}