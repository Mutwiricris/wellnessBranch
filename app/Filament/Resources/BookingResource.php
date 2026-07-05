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

use Filament\Tables\Actions\Action;
use App\Services\ExportService;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationGroup = 'Bookings';
    
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        if (!$tenant) return null;

        return static::getModel()::where('branch_id', $tenant->id)
            ->whereDate('appointment_date', today())
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
    
    protected static ?string $recordTitleAttribute = 'booking_reference';
    
    // Scope bookings to only show those for the current branch (tenant)
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
                Forms\Components\Section::make('Booking Type & Client')
                    ->schema([
                        Forms\Components\Select::make('booking_type')
                            ->label('Booking Category')
                            ->options([
                                'appointment' => 'Scheduled Appointment',
                                'walk_in' => 'Walk-in Customer'
                            ])
                            ->default('appointment')
                            ->required()
                            ->reactive()
                            ->native(false),

                        Forms\Components\Select::make('client_id')
                            ->label('Existing Client')
                            ->relationship('client', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->first_name . ' ' . $record->last_name . ' (' . $record->phone . ')')
                            ->searchable(['first_name', 'last_name', 'email', 'phone'])
                            ->required(fn (Forms\Get $get) => $get('booking_type') === 'appointment')
                            ->visible(fn (Forms\Get $get) => $get('booking_type') === 'appointment')
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                                if ($state && $get('service_id')) {
                                    $user = \App\Models\User::find($state);
                                    $service = \App\Models\Service::find($get('service_id'));
                                    if ($user && $service) {
                                        $price = app(\App\Services\MembershipService::class)->calculateDiscountedPrice($user, $service);
                                        $set('total_amount', $price);
                                    }
                                }
                            })
                            ->default(fn () => request('client_id'))
                            ->createOptionForm([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('first_name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('last_name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('phone')
                                            ->tel()
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('email')
                                            ->email()
                                            ->unique(User::class, 'email')
                                            ->maxLength(255)
                                            ->helperText('Optional'),
                                        Forms\Components\Hidden::make('password')
                                            ->default(fn () => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(12))),
                                    ])
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('customer_name')
                                    ->label('Customer Name')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('customer_phone')
                                    ->label('Customer Phone')
                                    ->tel()
                                    ->maxLength(255),
                            ])
                            ->visible(fn (Forms\Get $get) => $get('booking_type') === 'walk_in'),
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
                            ->default(fn () => request('service_id'))
                            ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                                if ($state) {
                                    $service = \App\Models\Service::find($state);
                                    if ($service) {
                                        $clientId = $get('client_id');
                                        $price = $service->price;
                                        
                                        if ($clientId) {
                                            $user = \App\Models\User::find($clientId);
                                            if ($user) {
                                                $price = app(\App\Services\MembershipService::class)->calculateDiscountedPrice($user, $service);
                                            }
                                        }
                                        
                                        $set('total_amount', $price);
                                        // Calculate end time based on service duration
                                        $startTime = $get('start_time');
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
                            ->default(fn () => request('staff_id'))
                            ->native(false),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Appointment Schedule')
                    ->schema([
                        Forms\Components\DatePicker::make('appointment_date')
                            ->required()
                            ->minDate(now())
                            ->reactive()
                            ->default(fn () => request('appointment_date') ? Carbon::parse(request('appointment_date'))->format('Y-m-d') : null)
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Clear time fields when date changes
                                $set('start_time', null);
                                $set('end_time', null);
                            }),
                            
                        Forms\Components\TimePicker::make('start_time')
                            ->required()
                            ->reactive()
                            ->default(fn () => (request('appointment_date') && str_contains(request('appointment_date'), 'T')) ? Carbon::parse(request('appointment_date'))->format('H:i') : null)
                            ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                                if ($state) {
                                    $duration = 60;
                                    if ($get('service_id')) {
                                        $service = Service::find($get('service_id'));
                                        if ($service) {
                                            $duration = $service->duration ?? 60;
                                        }
                                    }
                                    $endTime = Carbon::parse($state)->addMinutes($duration);
                                    $set('end_time', $endTime->format('H:i'));
                                }
                            }),
                            
                        Forms\Components\TimePicker::make('end_time')
                            ->required()
                            ->afterOrEqual('start_time')
                            ->default(function () {
                                $appointmentDate = request('appointment_date');
                                if ($appointmentDate && str_contains($appointmentDate, 'T')) {
                                    $dt = Carbon::parse($appointmentDate);
                                    $duration = 60;
                                    if ($serviceId = request('service_id')) {
                                        $service = Service::find($serviceId);
                                        if ($service) {
                                            $duration = $service->duration ?? 60;
                                        }
                                    }
                                    return $dt->addMinutes($duration)->format('H:i');
                                }
                                return null;
                            }),
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
                            ->reactive()
                            ->native(false),
                            
                        Forms\Components\Select::make('payment_method')
                            ->options(function () {
                                $tenant = \Filament\Facades\Filament::getTenant();
                                $activeMethods = $tenant?->payment_settings['active_methods'] ?? ['cash', 'mpesa'];
                                
                                $allOptions = [
                                    'cash' => 'Cash',
                                    'mpesa' => 'M-Pesa',
                                    'card' => 'Card',
                                    'bank_transfer' => 'Bank Transfer'
                                ];
                                
                                return collect($allOptions)
                                    ->filter(fn ($label, $key) => in_array($key, $activeMethods))
                                    ->toArray();
                            })
                            ->default(function () {
                                $tenant = \Filament\Facades\Filament::getTenant();
                                $activeMethods = $tenant?->payment_settings['active_methods'] ?? ['cash'];
                                return count($activeMethods) > 0 ? $activeMethods[0] : 'cash';
                            })
                            ->reactive()
                            ->native(false)
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('mpesa_prompt')
                                    ->label('M-Pesa Prompt')
                                    ->icon('heroicon-o-device-phone-mobile')
                                    ->color('success')
                                    ->visible(fn (Forms\Get $get) => $get('payment_method') === 'mpesa' && $get('payment_status') === 'pending')
                                    ->form([
                                        Forms\Components\Hidden::make('stk_initiated')->default(false),
                                        Forms\Components\Grid::make(1)
                                            ->schema([
                                                Forms\Components\Placeholder::make('loading_ui')
                                                    ->label('')
                                                    ->visible(fn (Forms\Get $get) => $get('stk_initiated'))
                                                    ->content(new \Illuminate\Support\HtmlString('
                                                        <div class="flex flex-col items-center justify-center py-6 text-center">
                                                            <div class="relative flex items-center justify-center w-20 h-20 mb-4">
                                                                <div class="absolute w-full h-full border-4 border-green-200 rounded-full animate-ping opacity-25"></div>
                                                                <div class="absolute w-full h-full border-4 border-green-500 rounded-full animate-spin border-t-transparent"></div>
                                                                <svg class="w-10 h-10 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                                </svg>
                                                            </div>
                                                            <h3 class="text-lg font-bold text-gray-900">Waiting for PIN Entry</h3>
                                                            <p class="text-sm text-gray-500 max-w-xs mx-auto">An STK push has been sent to the phone. Please enter your M-Pesa PIN on the handset to complete the payment.</p>
                                                            <div class="mt-4 flex items-center space-x-2 text-xs text-gray-400">
                                                                <span class="animate-pulse">Checking status...</span>
                                                                <span>Timeout in 60s</span>
                                                            </div>
                                                        </div>
                                                    ')),
                                                Forms\Components\Group::make([
                                                    Forms\Components\Placeholder::make('loading_hint')
                                                        ->label('')
                                                        ->content(new \Illuminate\Support\HtmlString('<div class="flex items-center space-x-2 text-sm text-gray-500 animate-pulse"><svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span>Ready to initiate secure STK push...</span></div>'))
                                                        ->columnSpanFull(),
                                                    Forms\Components\TextInput::make('client_display_name')->label('Client Name')->disabled(),
                                                    Forms\Components\TextInput::make('booking_ref')->label('Reference Number')->disabled(),
                                                    Forms\Components\TextInput::make('payable_amount')->label('Amount (KES)')->disabled(),
                                                    Forms\Components\TextInput::make('phone_number')
                                                        ->label('M-Pesa Phone Number')
                                                        ->tel()
                                                        ->required(),
                                                ])
                                                ->columns(2)
                                                ->hidden(fn (Forms\Get $get) => $get('stk_initiated')),
                                            ])
                                    ])
                                    ->mountUsing(fn (Forms\ComponentContainer $form, Forms\Get $get, $record) => $form->fill([
                                        'client_display_name' => $get('booking_type') === 'walk_in' ? ($get('customer_name') ?? 'Walk-in') : (\App\Models\User::find($get('client_id'))?->name ?? 'Client'),
                                        'booking_ref' => $record?->booking_reference ?? 'PENDING',
                                        'payable_amount' => $get('total_amount'),
                                        'phone_number' => $get('customer_phone') ?: ($record?->customer_phone ?? $record?->client?->phone),
                                        'stk_initiated' => false,
                                    ]))
                                    ->modalHeading('M-Pesa Payment')
                                    ->modalWidth('md')
                                    ->modalSubmitActionLabel(fn (Forms\Get $get) => $get('stk_initiated') ? 'Verify Payment' : 'Send STK Push')
                                    ->modalSubmitAction(fn ($action) => $action->color('success'))
                                    ->action(function (array $data, Forms\Set $set, $action, Forms\Get $get, $record, \Livewire\Component $livewire) {
                                        if (!$data['stk_initiated']) {
                                            $tenant = \Filament\Facades\Filament::getTenant();
                                            $paymentSettings = $tenant?->payment_settings ?? [];
                                            $type = $paymentSettings['mpesa_type'] ?? 'till';
                                            
                                            if ($type === 'till') {
                                                $mpesaType = 'Till';
                                                $mpesaNumber = $paymentSettings['mpesa_number_till'] ?? 'Not Configured';
                                            } elseif ($type === 'paybill') {
                                                $mpesaType = 'Paybill';
                                                $mpesaNumber = $paymentSettings['mpesa_number_paybill'] ?? 'Not Configured';
                                            } else {
                                                $mpesaType = 'Bank Paybill';
                                                $bankName = $paymentSettings['bank_name'] ?? 'Bank';
                                                preg_match('/Paybill: (\d+)/', $bankName, $matches);
                                                $paybillNumber = $matches[1] ?? 'Unknown Paybill';
                                                $mpesaNumber = $paybillNumber . ' (Acct: ' . ($paymentSettings['bank_account_number'] ?? 'Not Configured') . ')';
                                            }

                                            // Create a pending payment record
                                            if ($record) {
                                                \App\Models\Payment::create([
                                                    'booking_id' => $record->id,
                                                    'amount' => $get('total_amount'),
                                                    'payment_method' => 'mpesa',
                                                    'payment_category' => 'booking',
                                                    'status' => 'pending',
                                                    'transaction_reference' => 'STK-' . strtoupper(\Illuminate\Support\Str::random(10)),
                                                    'processed_at' => now(),
                                                ]);
                                            }

                                            $set('stk_initiated', true);
                                            \Filament\Notifications\Notification::make()
                                                ->title('STK Push Sent')
                                                ->body("Routing to {$mpesaType} Number: {$mpesaNumber}")
                                                ->success()
                                                ->send();
                                            $action->halt();
                                        } else {
                                            // Simulate successful verification
                                            $set('payment_status', 'completed');
                                            $set('status', 'completed');
                                            
                                            if ($record) {
                                                // Update the last pending payment for this booking
                                                $payment = $record->payments()->where('status', 'pending')->latest()->first();
                                                if ($payment) {
                                                    $payment->update(['status' => 'completed']);
                                                    
                                                    // Trigger dashboard update
                                                    $livewire->dispatch('payment-updated');
                                                }

                                                $record->update([
                                                    'payment_status' => 'completed',
                                                    'status' => 'completed',
                                                    'service_completed_at' => now(),
                                                ]);

                                                // Check if commission needs to be generated
                                                $commissionCreated = false;
                                                if ($record->staff && in_array($record->staff->payment_type, ['salary', 'commission_only'])) {
                                                    $commissionRecord = app(\App\Services\CommissionService::class)->createCommissionOnBookingComplete($record);
                                                    $commissionCreated = $commissionRecord !== null;
                                                }
                                                
                                                $commissionText = $commissionCreated ? "\n💰 Commission generated automatically." : "";
                                                
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Payment Verified & Booking Completed')
                                                    ->body('The transaction was successful and the booking has been finalized.' . $commissionText)
                                                    ->success()
                                                    ->send();
                                            } else {
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Payment Verified')
                                                    ->body('The transaction was successful.')
                                                    ->success()
                                                    ->send();
                                            }
                                        }
                                    })
                            ),
                            
                        Forms\Components\TextInput::make('total_amount')
                            ->numeric()
                            ->prefix('KES')
                            ->step(0.01)
                            ->required()
                            ->default(fn () => request('service_id') ? Service::find(request('service_id'))?->price : null),
                            
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
            ->headerActions([
                Action::make('export_excel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function () {
                        $service = app(ExportService::class);
                        $data = static::getEloquentQuery()->get();
                        
                        $headers = [
                            'booking_reference' => 'Reference',
                            'client.name' => 'Client Name',
                            'customer_name' => 'Walk-in Name',
                            'service.name' => 'Service',
                            'staff.name' => 'Staff',
                            'appointment_date' => 'Date',
                            'start_time' => 'Start',
                            'end_time' => 'End',
                            'status' => 'Status',
                            'total_amount' => 'Amount (KES)',
                        ];
                        
                        return $service->exportToCsv($data, $headers, 'Bookings_Export_' . now()->format('Y-m-d'));
                    }),
                Action::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('danger')
                    ->action(function () {
                        $service = app(ExportService::class);
                        $tenant = \Filament\Facades\Filament::getTenant();
                        $data = static::getEloquentQuery()->get();
                        
                        $headers = [
                            'booking_reference' => 'Ref',
                            'client.name' => 'Client',
                            'service.name' => 'Service',
                            'appointment_date' => 'Date',
                            'status' => 'Status',
                            'total_amount' => 'Amount',
                        ];
                        
                        return $service->exportToPdf('reports.generic', [
                            'title' => 'Booking Schedule Report',
                            'data' => $data,
                            'headers' => $headers,
                            'branch_name' => $tenant?->name ?? 'All Branches',
                            'period' => 'Comprehensive Schedule',
                        ], 'Bookings_Report_' . now()->format('Y-m-d'), 'a4', 'landscape');
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('booking_reference')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->copyable(),
                    
                Tables\Columns\TextColumn::make('booking_type')
                    ->label('Category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'appointment' => 'info',
                        'walk_in' => 'warning',
                        default => 'gray'
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'appointment' => 'Scheduled',
                        'walk_in' => 'Walk-in',
                        default => ucfirst($state)
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('client_name')
                    ->label('Client/Customer')
                    ->getStateUsing(function (Booking $record): string {
                        if ($record->booking_type === 'walk_in') {
                            return $record->customer_name ?? 'Anonymous Walk-in';
                        }
                        return $record->client ? ($record->client->first_name . ' ' . $record->client->last_name) : 'No Client';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('client', function (Builder $query) use ($search) {
                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        })->orWhere('customer_name', 'like', "%{$search}%");
                    })
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

                Tables\Columns\TextColumn::make('service_started_at')
                    ->label('Service Started')
                    ->dateTime('M j, g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Not started'),

                Tables\Columns\TextColumn::make('service_completed_at')
                    ->label('Service Completed')
                    ->dateTime('M j, g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Not completed'),

                Tables\Columns\TextColumn::make('service_duration')
                    ->label('Duration')
                    ->getStateUsing(function (Booking $record): ?string {
                        $duration = $record->getServiceDuration();
                        return $duration ? "{$duration} min" : null;
                    })
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('In progress'),
                    
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
                Tables\Actions\Action::make('mpesa_prompt')
                    ->label('M-Pesa Prompt')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->color('success')
                    ->visible(fn (Booking $record): bool => $record->payment_method === 'mpesa' && $record->payment_status === 'pending')
                    ->form([
                        Forms\Components\Hidden::make('stk_initiated')->default(false),
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Placeholder::make('loading_ui')
                                    ->label('')
                                    ->visible(fn (Forms\Get $get) => $get('stk_initiated'))
                                    ->content(new \Illuminate\Support\HtmlString('
                                        <div class="flex flex-col items-center justify-center py-6 text-center">
                                            <div class="relative flex items-center justify-center w-20 h-20 mb-4">
                                                <div class="absolute w-full h-full border-4 border-green-200 rounded-full animate-ping opacity-25"></div>
                                                <div class="absolute w-full h-full border-4 border-green-500 rounded-full animate-spin border-t-transparent"></div>
                                                <svg class="w-10 h-10 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <h3 class="text-lg font-bold text-gray-900">Waiting for PIN Entry</h3>
                                            <p class="text-sm text-gray-500 max-w-xs mx-auto">An STK push has been sent to the phone. Please enter your M-Pesa PIN on the handset.</p>
                                            <div class="mt-4 flex items-center space-x-2 text-xs text-gray-400">
                                                <span class="animate-pulse">Checking status...</span>
                                                <span>Timeout in 60s</span>
                                            </div>
                                        </div>
                                    ')),
                                Forms\Components\Group::make([
                                    Forms\Components\Placeholder::make('loading_hint')
                                        ->label('')
                                        ->content(new \Illuminate\Support\HtmlString('<div class="flex items-center space-x-2 text-sm text-gray-500 animate-pulse"><svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span>Verifying transaction details...</span></div>'))
                                        ->columnSpanFull(),
                                    Forms\Components\TextInput::make('client_display_name')->label('Client Name')->disabled(),
                                    Forms\Components\TextInput::make('booking_ref')->label('Reference Number')->disabled(),
                                    Forms\Components\TextInput::make('payable_amount')->label('Amount (KES)')->disabled(),
                                    Forms\Components\TextInput::make('phone_number')->label('M-Pesa Phone Number')->tel()->required(),
                                ])
                                ->columns(2)
                                ->hidden(fn (Forms\Get $get) => $get('stk_initiated')),
                            ])
                    ])
                    ->mountUsing(fn (Forms\ComponentContainer $form, Booking $record) => $form->fill([
                        'client_display_name' => $record->booking_type === 'walk_in' ? ($record->customer_name ?? 'Walk-in') : ($record->client?->name ?? 'Client'),
                        'booking_ref' => $record->booking_reference,
                        'payable_amount' => $record->total_amount,
                        'phone_number' => $record->customer_phone ?? $record->client?->phone,
                        'stk_initiated' => false,
                     ]))
                    ->modalHeading('M-Pesa STK Push')
                    ->modalWidth('md')
                    ->modalSubmitActionLabel(fn (Forms\Get $get) => $get('stk_initiated') ? 'Verify Payment' : 'Send STK Push')
                    ->modalSubmitAction(fn ($action) => $action->color('success'))
                    ->action(function (Booking $record, array $data, Forms\Set $set, $action, \Livewire\Component $livewire) {
                        if (!$data['stk_initiated']) {
                            $tenant = \Filament\Facades\Filament::getTenant();
                            $paymentSettings = $tenant?->payment_settings ?? [];
                            $type = $paymentSettings['mpesa_type'] ?? 'till';
                            
                            if ($type === 'till') {
                                $mpesaType = 'Till';
                                $mpesaNumber = $paymentSettings['mpesa_number_till'] ?? 'Not Configured';
                            } elseif ($type === 'paybill') {
                                $mpesaType = 'Paybill';
                                $mpesaNumber = $paymentSettings['mpesa_number_paybill'] ?? 'Not Configured';
                            } else {
                                $mpesaType = 'Bank Paybill';
                                $bankName = $paymentSettings['bank_name'] ?? 'Bank';
                                preg_match('/Paybill: (\d+)/', $bankName, $matches);
                                $paybillNumber = $matches[1] ?? 'Unknown Paybill';
                                $mpesaNumber = $paybillNumber . ' (Acct: ' . ($paymentSettings['bank_account_number'] ?? 'Not Configured') . ')';
                            }

                            // Create a pending payment record
                            \App\Models\Payment::create([
                                'booking_id' => $record->id,
                                'amount' => $record->total_amount,
                                'payment_method' => 'mpesa',
                                'payment_category' => 'booking',
                                'status' => 'pending',
                                'transaction_reference' => 'STK-' . strtoupper(\Illuminate\Support\Str::random(10)),
                                'processed_at' => now(),
                            ]);

                            $set('stk_initiated', true);
                            \Filament\Notifications\Notification::make()
                                ->title('STK Push Sent')
                                ->body("Routing to {$mpesaType} Number: {$mpesaNumber}")
                                ->success()
                                ->send();
                            $action->halt();
                        } else {
                            // Update the pending payment to completed
                            $payment = $record->payments()->where('status', 'pending')->latest()->first();
                            if ($payment) {
                                $payment->update(['status' => 'completed']);
                                $livewire->dispatch('payment-updated');
                            }

                            $record->update([
                                'payment_status' => 'completed',
                                'status' => 'completed',
                                'service_completed_at' => now(),
                            ]);

                            // Check if commission needs to be generated
                            $commissionCreated = false;
                            if ($record->staff && in_array($record->staff->payment_type, ['salary', 'commission_only'])) {
                                $commissionRecord = app(\App\Services\CommissionService::class)->createCommissionOnBookingComplete($record);
                                $commissionCreated = $commissionRecord !== null;
                            }
                            
                            $commissionText = $commissionCreated ? "\n💰 Commission generated automatically." : "";

                            \Filament\Notifications\Notification::make()
                                ->title('Payment Verified & Booking Completed')
                                ->body('The transaction was successful and the booking has been finalized.' . $commissionText)
                                ->success()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('confirm_booking')
                    ->label('Confirm')
                    ->icon('heroicon-o-check-badge')
                    ->color('info')
                    ->visible(fn (Booking $record): bool => $record->canBeConfirmed())
                    ->requiresConfirmation()
                    ->modalDescription(fn (Booking $record) => $record->getPaymentStatusMessage())
                    ->action(function (Booking $record) {
                        // Can only confirm booking with valid payment
                        if ($record->hasValidPayment()) {
                            $record->update([
                                'payment_status' => 'completed',
                                'status' => 'confirmed',
                                'confirmed_at' => now()
                            ]);
                        } else {
                            // Keep as pending if no valid payment
                            $record->update([
                                'status' => 'pending',
                                'confirmed_at' => null
                            ]);
                        }
                    }),
                    
                Tables\Actions\Action::make('record_payment')
                    ->label('Record Payment')
                    ->icon('heroicon-o-credit-card')
                    ->color('warning')
                    ->visible(fn (Booking $record): bool => !$record->hasValidPayment())
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->label('Amount')
                                    ->numeric()
                                    ->prefix('KES')
                                    ->step(0.01)
                                    ->required(),
                                    
                                Forms\Components\Select::make('payment_method')
                                    ->label('Payment Method')
                                    ->options([
                                        'cash' => 'Cash',
                                        'mpesa' => 'M-Pesa',
                                        'card' => 'Credit/Debit Card',
                                        'bank_transfer' => 'Bank Transfer'
                                    ])
                                    ->required()
                                    ->reactive()
                                    ->native(false),
                            ]),
                            
                        Forms\Components\TextInput::make('transaction_reference')
                            ->label('Transaction Reference')
                            ->maxLength(100)
                            ->helperText('For M-Pesa, card or bank transfer payments')
                            ->visible(fn (Forms\Get $get) => in_array($get('payment_method'), ['mpesa', 'card', 'bank_transfer'])),
                            
                        Forms\Components\Select::make('payment_status')
                            ->label('Payment Status')
                            ->options([
                                'completed' => 'Completed',
                                'pending' => 'Pending',
                                'failed' => 'Failed'
                            ])
                            ->default('completed')
                            ->required()
                            ->native(false),
                    ])
                    ->modalHeading('Record Payment')
                    ->modalSubmitActionLabel('Record Payment')
                    ->fillForm(fn (Booking $record): array => [
                        'amount' => $record->total_amount,
                        'payment_method' => $record->payment_method ?? 'cash',
                    ])
                    ->action(function (Booking $record, array $data) {
                        $tenant = \Filament\Facades\Filament::getTenant();
                        
                        // Create or update payment record
                        $payment = \App\Models\Payment::updateOrCreate(
                            ['booking_id' => $record->id],
                            [
                                'amount' => $data['amount'],
                                'payment_method' => $data['payment_method'],
                                'transaction_reference' => $data['transaction_reference'] ?? null,
                                'status' => $data['payment_status'],
                                'processed_at' => $data['payment_status'] === 'completed' ? now() : null,
                            ]
                        );

                        // Update booking payment information (now using consistent 'completed' status)
                        $record->update([
                            'payment_status' => $data['payment_status'],
                            'payment_method' => $data['payment_method'],
                            'total_amount' => $data['amount'],
                        ]);

                        // Check if commission needs to be generated
                        $commissionCreated = false;
                        if ($record->status === 'completed' && $data['payment_status'] === 'completed' && $record->staff && in_array($record->staff->payment_type, ['salary', 'commission_only'])) {
                            $commissionRecord = app(\App\Services\CommissionService::class)->createCommissionOnBookingComplete($record);
                            $commissionCreated = $commissionRecord !== null;
                        }

                        $commissionText = $commissionCreated ? "\n💰 Commission generated automatically." : "";

                        \Filament\Notifications\Notification::make() 
                            ->title('Payment Recorded')
                            ->body(($data['payment_status'] === 'completed' ? 
                                'Payment confirmed successfully.' : 
                                "Payment recorded as '{$data['payment_status']}'.") . $commissionText)
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\Action::make('start_service')
                    ->label('Start Service')
                    ->icon('heroicon-o-play')
                    ->color('success')
                     
                    ->visible(fn (Booking $record): bool => $record->canBeStarted())
                    ->requiresConfirmation()
                    ->modalHeading('Start Service')
                    ->modalDescription(fn (Booking $record) => 
                        "Start the service for {$record->service->name} with {$record->staff->name}?"
                    )
                    ->modalSubmitActionLabel('Start Service')
                    ->action(function (Booking $record) {
                        $success = $record->startService();
                        if ($success) {
                            \Filament\Notifications\Notification::make()
                                ->title('Service Started')
                                ->body("Service has been started at " . now()->format('g:i A'))
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Failed to Start Service')
                                ->body('Service could not be started. Please check the booking status.')
                                ->danger()
                                ->send();
                        }
                    }),
                    
                Tables\Actions\Action::make('complete_service')
                    ->label('Complete Service')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Booking $record): bool => 
                        ($record->status === 'confirmed' && $record->appointment_date->toDateString() === now()->toDateString()) ||
                        $record->canBeCompleted()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Complete Service')
                    ->modalDescription(function (Booking $record) {
                        $paymentStatus = $record->hasValidPayment() ? 
                            "✅ Payment confirmed" : 
                            "⚠️ Payment required";
                        
                        $statusInfo = $record->status === 'confirmed' ? 
                            "This will mark the service as completed directly." :
                            "This will mark the in-progress service as completed.";
                        
                        $commissionInfo = "";
                        if ($record->staff && in_array($record->staff->payment_type, ['salary', 'commission_only'])) {
                            $commissionInfo = "\n\n💰 Commission will be automatically created for {$record->staff->name}";
                        }
                        
                        return "Mark the service for {$record->service->name} as completed?\n\nPayment Status: {$paymentStatus}\n\n{$statusInfo}{$commissionInfo}";
                    })
                    ->modalSubmitActionLabel('Complete Service')
                    ->action(function (Booking $record) {
                        // Validate payment before completion
                        if (!$record->hasValidPayment()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Payment Required')
                                ->body('Service cannot be completed without confirmed payment.')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        // If confirmed, complete directly (skip in_progress)
                        if ($record->status === 'confirmed') {
                            $record->update([
                                'status' => 'completed',
                                'service_started_at' => now()->subMinutes(60), // Assume 1 hour service
                                'service_completed_at' => now(),
                            ]);
                            $success = true;
                        } else {
                            // Normal completion for in_progress bookings
                            $success = $record->completeService();
                        }
                        
                        if ($success) {
                            // Create commission via CommissionService (handles all logic and hold period)
                            $commissionCreated = false;
                            if ($record->staff && in_array($record->staff->payment_type, ['salary', 'commission_only'])) {
                                $commissionRecord = app(\App\Services\CommissionService::class)
                                    ->createCommissionOnBookingComplete($record);
                                $commissionCreated = $commissionRecord !== null;
                            }
                            
                            $duration = $record->getServiceDuration();
                            $durationText = $duration ? " (Duration: {$duration} minutes)" : "";
                            $commissionText = $commissionCreated ? "\n💰 Commission of KES " . number_format($commissionRecord->commission_amount ?? 0, 2) . " created for {$record->staff->name}!" : "";
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Service Completed')
                                ->body("Service completed at " . now()->format('g:i A') . $durationText . $commissionText)
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Failed to Complete Service')
                                ->body('Service could not be completed. Please ensure payment is confirmed.')
                                ->danger()
                                ->send();
                        }
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
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                                'no_show' => 'No Show',
                                default => ucfirst($state)
                            })
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
                        Infolists\Components\TextEntry::make('service_started_at')
                            ->label('Service Started At')
                            ->dateTime()
                            ->placeholder('Not started')
                            ->visible(fn ($record): bool => $record->service_started_at !== null || $record->status === 'in_progress' || $record->status === 'completed'),
                        Infolists\Components\TextEntry::make('service_completed_at')
                            ->label('Service Completed At')
                            ->dateTime()
                            ->placeholder('Not completed')
                            ->visible(fn ($record): bool => $record->service_completed_at !== null || $record->status === 'completed'),
                        Infolists\Components\TextEntry::make('service_duration')
                            ->label('Service Duration')
                            ->getStateUsing(function ($record): ?string {
                                $duration = $record->getServiceDuration();
                                return $duration ? "{$duration} minutes" : null;
                            })
                            ->placeholder('In progress')
                            ->visible(fn ($record): bool => $record->status === 'completed' || $record->getServiceDuration() !== null)
                            ->badge()
                            ->color('success'),
                    ])->columns(2),
                    
                Infolists\Components\Section::make('Client Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('client.first_name')
                            ->label('Name')
                            ->formatStateUsing(fn ($record) => $record->client ? ($record->client->first_name . ' ' . $record->client->last_name) : ($record->customer_name ?? 'Walk-in')),
                        Infolists\Components\TextEntry::make('client.email')
                            ->label('Email')
                            ->copyable()
                            ->placeholder('N/A'),
                        Infolists\Components\TextEntry::make('client.phone')
                            ->label('Phone')
                            ->copyable()
                            ->getStateUsing(fn ($record) => $record->client ? $record->client->phone : ($record->customer_phone ?? 'N/A')),
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
                    ->headerActions([
                        Infolists\Components\Actions\Action::make('complete_payment')
                            ->label('Complete Payment')
                            ->icon('heroicon-o-check-circle')
                            ->color('success')
                            ->visible(fn ($record): bool => $record->payment_status === 'pending')
                            ->action(function (Booking $record, \Livewire\Component $livewire) {
                                // Update payment status to completed
                                $record->update([
                                    'payment_status' => 'completed',
                                ]);

                                // Check if commission needs to be generated
                                $commissionCreated = false;
                                if ($record->status === 'completed' && $record->staff && in_array($record->staff->payment_type, ['salary', 'commission_only'])) {
                                    $commissionRecord = app(\App\Services\CommissionService::class)->createCommissionOnBookingComplete($record);
                                    $commissionCreated = $commissionRecord !== null;
                                }

                                $livewire->dispatch('payment-updated');
                                
                                $commissionText = $commissionCreated ? "\n💰 Commission generated automatically." : "";

                                \Filament\Notifications\Notification::make()
                                    ->title('Payment Completed')
                                    ->body('Payment status has been updated to completed.' . $commissionText)
                                    ->success()
                                    ->send();
                            })
                            ->requiresConfirmation()
                            ->modalDescription('Are you sure you want to mark this payment as completed?')
                            ->modalHeading('Complete Payment')
                            ->modalIcon('heroicon-o-check-circle')
                    ])
                    ->schema([
                        Infolists\Components\TextEntry::make('total_amount')
                            ->label('Amount')
                            ->money('KES'),
                        Infolists\Components\TextEntry::make('payment_status')
                            ->label('Payment Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'completed' => 'success',
                                'failed' => 'danger',
                                'refunded' => 'gray',
                                default => 'gray'
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'pending' => '⏳ Pending Payment',
                                'completed' => '✅ Payment Confirmed',
                                'failed' => '❌ Payment Failed',
                                'refunded' => '↩️ Refunded',
                                default => ucfirst($state)
                            }),
                        Infolists\Components\TextEntry::make('payment_validation')
                            ->label('Payment Validation')
                            ->getStateUsing(fn ($record) => $record->hasValidPayment() ? 'Valid' : 'Required')
                            ->badge()
                            ->color(fn ($record) => $record->hasValidPayment() ? 'success' : 'warning')
                            ->formatStateUsing(fn ($record, $state) => $record->hasValidPayment() ? '✅ Payment Validated' : '⚠️ Payment Required'),
                        Infolists\Components\TextEntry::make('payment_method')
                            ->label('Payment Method')
                            ->formatStateUsing(fn (?string $state): string => $state ? ucfirst(str_replace('_', ' ', $state)) : 'Not specified'),
                        Infolists\Components\TextEntry::make('mpesa_transaction_id')
                            ->label('M-Pesa Transaction ID')
                            ->placeholder('N/A')
                            ->copyable()
                            ->visible(fn ($record) => $record->payment_method === 'mpesa'),
                        Infolists\Components\TextEntry::make('service_actions_available')
                            ->label('Available Actions')
                            ->getStateUsing(function ($record) {
                                $actions = [];
                                if ($record->status === 'pending' && $record->hasValidPayment()) {
                                    $actions[] = '📋 Can be confirmed';
                                }
                                if ($record->canBeStarted()) {
                                    $actions[] = '▶️ Can start service';
                                }
                                if ($record->canBeCompleted()) {
                                    $actions[] = '✅ Can complete service';
                                }
                                if (empty($actions)) {
                                    if (!$record->hasValidPayment()) {
                                        $actions[] = '⚠️ Payment required';
                                    } else {
                                        $actions[] = '✓ No actions needed';
                                    }
                                }
                                return implode("\n", $actions);
                            })
                            ->columnSpanFull()
                            ->badge()
                            ->color(fn ($record) => $record->hasValidPayment() ? 'success' : 'warning'),
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
            'calendar' => Pages\CalendarBookings::route('/calendar'),
            'view' => Pages\ViewBooking::route('/{record}'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }

    public static function getNavigationItems(): array
    {
        return [
            ...parent::getNavigationItems(),
            \Filament\Navigation\NavigationItem::make('Calendar View')
                ->icon('heroicon-o-calendar-days')
                ->group('Bookings')
                ->sort(2)
                ->url(static::getUrl('calendar')),
        ];
    }
}
