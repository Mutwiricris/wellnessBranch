<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Filament\Resources\BookingResource\RelationManagers;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Carbon\Carbon;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationGroup = 'Bookings';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $recordTitleAttribute = 'booking_reference';
    
    // Scope bookings to only show those for the current branch
    public static function getEloquentQuery(): Builder
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        
        return parent::getEloquentQuery()
            ->where('branch_id', $tenant->id)
            ->with(['client', 'service', 'staff', 'payment']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Client Information')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->label('Client')
                            ->relationship('client', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->first_name . ' ' . $record->last_name . ' (' . $record->email . ')')
                            ->searchable(['first_name', 'last_name', 'email', 'phone'])
                            ->required()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('first_name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('last_name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('email')
                                            ->email()
                                            ->unique(User::class, 'email')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('phone')
                                            ->tel()
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Select::make('gender')
                                            ->options([
                                                'male' => 'Male',
                                                'female' => 'Female',
                                                'other' => 'Other',
                                                'prefer_not_to_say' => 'Prefer not to say'
                                            ])
                                            ->native(false),
                                        Forms\Components\DatePicker::make('date_of_birth')
                                            ->maxDate(now()),
                                        Forms\Components\Textarea::make('allergies')
                                            ->columnSpanFull()
                                            ->rows(2),
                                    ])
                            ])
                    ])->columns(1),
                    
                Forms\Components\Section::make('Service Details')
                    ->schema([
                        Forms\Components\Select::make('service_id')
                            ->label('Service')
                            ->options(function () {
                                $tenant = \Filament\Facades\Filament::getTenant();
                                return Service::whereHas('branches', function (Builder $query) use ($tenant) {
                                    $query->where('branch_id', $tenant->id);
                                })->pluck('name', 'id');
                            })
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $service = Service::find($state);
                                    if ($service) {
                                        $set('total_amount', $service->price);
                                        // Calculate end time based on service duration
                                        $startTime = request()->get('start_time');
                                        if ($startTime) {
                                            $endTime = Carbon::parse($startTime)->addMinutes($service->duration ?? 60);
                                            $set('end_time', $endTime->format('H:i'));
                                        }
                                    }
                                }
                            })
                            ->native(false),
                            
                        Forms\Components\Select::make('staff_id')
                            ->label('Staff Member')
                            ->options(function (Forms\Get $get) {
                                $tenant = \Filament\Facades\Filament::getTenant();
                                $serviceId = $get('service_id');
                                
                                $query = Staff::whereHas('branches', function (Builder $query) use ($tenant) {
                                    $query->where('branch_id', $tenant->id);
                                });
                                
                                if ($serviceId) {
                                    $query->whereHas('services', function (Builder $query) use ($serviceId) {
                                        $query->where('service_id', $serviceId);
                                    });
                                }
                                
                                return $query->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->native(false),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Appointment Schedule')
                    ->schema([
                        Forms\Components\DatePicker::make('appointment_date')
                            ->required()
                            ->minDate(now())
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Clear time fields when date changes
                                $set('start_time', null);
                                $set('end_time', null);
                            }),
                            
                        Forms\Components\TimePicker::make('start_time')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                                if ($state && $get('service_id')) {
                                    $service = Service::find($get('service_id'));
                                    if ($service) {
                                        $endTime = Carbon::parse($state)->addMinutes($service->duration ?? 60);
                                        $set('end_time', $endTime->format('H:i'));
                                    }
                                }
                            }),
                            
                        Forms\Components\TimePicker::make('end_time')
                            ->required()
                            ->afterOrEqual('start_time'),
                    ])->columns(3),
                    
                Forms\Components\Section::make('Booking Details')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                                'no_show' => 'No Show'
                            ])
                            ->default('pending')
                            ->required()
                            ->native(false),
                            
                        Forms\Components\Select::make('payment_status')
                            ->options([
                                'pending' => 'Pending',
                                'completed' => 'Completed',
                                'failed' => 'Failed',
                                'refunded' => 'Refunded'
                            ])
                            ->default('pending')
                            ->required()
                            ->native(false),
                            
                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'cash' => 'Cash',
                                'mpesa' => 'M-Pesa',
                                'card' => 'Card',
                                'bank_transfer' => 'Bank Transfer'
                            ])
                            ->native(false),
                            
                        Forms\Components\TextInput::make('total_amount')
                            ->numeric()
                            ->prefix('KES')
                            ->step(0.01)
                            ->required(),
                            
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                            
                        Forms\Components\Textarea::make('cancellation_reason')
                            ->rows(2)
                            ->columnSpanFull()
                            ->hidden(fn (Forms\Get $get): bool => $get('status') !== 'cancelled'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('booking_reference')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->copyable(),
                    
                Tables\Columns\TextColumn::make('client.first_name')
                    ->label('Client')
                    ->formatStateUsing(fn ($record) => $record->client->first_name . ' ' . $record->client->last_name)
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('service.name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('staff.name')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Unassigned'),
                    
                Tables\Columns\TextColumn::make('appointment_date')
                    ->date()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Time')
                    ->formatStateUsing(fn ($record) => $record->start_time . ' - ' . $record->end_time)
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'in_progress' => 'primary',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'no_show' => 'gray',
                        default => 'gray'
                    }),
                    
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'gray',
                        default => 'gray'
                    }),
                    
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('KES')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'no_show' => 'No Show'
                    ])
                    ->multiple(),
                    
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded'
                    ])
                    ->multiple(),
                    
                Tables\Filters\SelectFilter::make('service_id')
                    ->label('Service')
                    ->relationship('service', 'name')
                    ->multiple()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('staff_id')
                    ->label('Staff')
                    ->relationship('staff', 'name')
                    ->multiple()
                    ->preload(),
                    
                Tables\Filters\Filter::make('appointment_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('appointment_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('appointment_date', '<=', $date),
                            );
                    }),
                    
                Tables\Filters\Filter::make('today')
                    ->query(fn (Builder $query): Builder => $query->whereDate('appointment_date', today()))
                    ->label('Today\'s Appointments'),
                    
                Tables\Filters\Filter::make('upcoming')
                    ->query(fn (Builder $query): Builder => $query->where('appointment_date', '>=', today()))
                    ->label('Upcoming'),
            ])
            ->actions([
                Tables\Actions\Action::make('confirm_booking')
                    ->label('Confirm')
                    ->icon('heroicon-o-check-badge')
                    ->color('info')
                    ->visible(fn (Booking $record): bool => $record->canBeConfirmed())
                    ->requiresConfirmation()
                    ->modalDescription(fn (Booking $record) => $record->getPaymentStatusMessage())
                    ->action(function (Booking $record) {
                        $record->updateStatusWithPayment('confirmed');
                    }),
                    
                Tables\Actions\Action::make('payment_required')
                    ->label('Payment Required')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning')
                    ->visible(fn (Booking $record): bool => 
                        $record->status === 'pending' && $record->requiresPayment()
                    )
                    ->url(fn (Booking $record) => route('filament.admin.resources.payments.create', [
                        'booking_id' => $record->id
                    ]))
                    ->openUrlInNewTab(),
                    
                Tables\Actions\Action::make('start_service')
                    ->label('Start')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn (Booking $record): bool => $record->canBeStarted())
                    ->requiresConfirmation()
                    ->modalDescription(fn (Booking $record) => 
                        $record->hasValidPayment() 
                            ? 'Ready to start service - payment verified'
                            : 'Cannot start - payment required'
                    )
                    ->action(function (Booking $record) {
                        $record->updateStatusWithPayment('in_progress');
                    }),
                    
                Tables\Actions\Action::make('complete_service')
                    ->label('Complete')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Booking $record): bool => $record->canBeCompleted())
                    ->requiresConfirmation()
                    ->modalDescription(fn (Booking $record) => 
                        $record->hasValidPayment() 
                            ? 'Ready to complete service - payment verified'
                            : 'Cannot complete - payment required'
                    )
                    ->action(function (Booking $record) {
                        $record->updateStatusWithPayment('completed');
                    }),
                    
                Tables\Actions\Action::make('cancel_booking')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Booking $record): bool => $record->canBeCancelled())
                    ->form([
                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label('Cancellation Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Booking $record, array $data) {
                        $record->update([
                            'status' => 'cancelled',
                            'cancellation_reason' => $data['cancellation_reason'],
                            'cancelled_at' => now(),
                        ]);
                    }),
                    
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('confirm_bookings')
                        ->label('Confirm Selected')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function (Booking $booking) {
                                if ($booking->status === 'pending') {
                                    $booking->updateStatusWithPayment('confirmed');
                                }
                            });
                        }),
                        
                    Tables\Actions\BulkAction::make('cancel_bookings')
                        ->label('Cancel Selected')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('cancellation_reason')
                                ->label('Cancellation Reason')
                                ->required()
                                ->rows(3),
                        ])
                        ->requiresConfirmation()
                        ->action(function ($records, array $data) {
                            $records->each(function (Booking $booking) use ($data) {
                                if ($booking->canBeCancelled()) {
                                    $booking->update([
                                        'status' => 'cancelled',
                                        'cancellation_reason' => $data['cancellation_reason'],
                                        'cancelled_at' => now(),
                                    ]);
                                }
                            });
                        }),
                ]),
            ])
            ->defaultSort('appointment_date', 'desc')
            ->groups([
                Tables\Grouping\Group::make('appointment_date')
                    ->label('Date')
                    ->date()
                    ->collapsible(),
                Tables\Grouping\Group::make('status')
                    ->label('Status')
                    ->collapsible(),
                Tables\Grouping\Group::make('staff.name')
                    ->label('Staff')
                    ->collapsible(),
            ])
            ->recordUrl(
                fn (Booking $record): string => BookingResource::getUrl('view', ['record' => $record])
            );
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Booking Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('booking_reference')
                            ->label('Reference')
                            ->copyable()
                            ->weight(FontWeight::Bold),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'confirmed' => 'info',
                                'in_progress' => 'primary',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                'no_show' => 'gray',
                                default => 'gray'
                            }),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Booked On')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('confirmed_at')
                            ->label('Confirmed At')
                            ->dateTime()
                            ->placeholder('Not confirmed'),
                    ])->columns(2),
                    
                Infolists\Components\Section::make('Client Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('client.first_name')
                            ->label('Name')
                            ->formatStateUsing(fn ($record) => $record->client->first_name . ' ' . $record->client->last_name),
                        Infolists\Components\TextEntry::make('client.email')
                            ->label('Email')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('client.phone')
                            ->label('Phone')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('client.allergies')
                            ->label('Allergies')
                            ->placeholder('None specified'),
                    ])->columns(2),
                    
                Infolists\Components\Section::make('Service Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('service.name')
                            ->label('Service'),
                        Infolists\Components\TextEntry::make('staff.name')
                            ->label('Staff')
                            ->placeholder('Unassigned'),
                        Infolists\Components\TextEntry::make('appointment_date')
                            ->label('Date')
                            ->date(),
                        Infolists\Components\TextEntry::make('start_time')
                            ->label('Time')
                            ->formatStateUsing(fn ($record) => $record->start_time . ' - ' . $record->end_time),
                    ])->columns(2),
                    
                Infolists\Components\Section::make('Payment Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_amount')
                            ->label('Amount')
                            ->money('KES'),
                        Infolists\Components\TextEntry::make('payment_status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'completed' => 'success',
                                'failed' => 'danger',
                                'refunded' => 'gray',
                                default => 'gray'
                            }),
                        Infolists\Components\TextEntry::make('payment_method')
                            ->label('Method')
                            ->formatStateUsing(fn (?string $state): string => $state ? ucfirst(str_replace('_', ' ', $state)) : 'Not specified'),
                        Infolists\Components\TextEntry::make('mpesa_transaction_id')
                            ->label('M-Pesa Transaction ID')
                            ->placeholder('N/A')
                            ->copyable(),
                    ])->columns(2),
                    
                Infolists\Components\Section::make('Additional Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Notes')
                            ->placeholder('No notes')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('cancellation_reason')
                            ->label('Cancellation Reason')
                            ->placeholder('N/A')
                            ->visible(fn ($record) => $record->status === 'cancelled')
                            ->columnSpanFull(),
                    ])->columns(1),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'view' => Pages\ViewBooking::route('/{record}'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
            'calendar' => Pages\CalendarBookings::route('/calendar'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        try {
            $tenant = \Filament\Facades\Filament::getTenant();
            
            if (!$tenant || !auth()->check()) {
                return null;
            }
            
            $count = static::getModel()::where('branch_id', $tenant->id)
                ->whereDate('appointment_date', today())
                ->whereIn('status', ['pending', 'confirmed'])
                ->count();
                
            return $count > 0 ? (string) $count : null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}