<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers;
use App\Models\Payment;
use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;

use Filament\Tables\Actions\Action;
use App\Services\ExportService;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    
    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'payment_reference';

    public static function getEloquentQuery(): Builder
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        
        // Create a completely fresh query that bypasses any global scopes
        $query = static::getModel()::withoutGlobalScopes();
        
        // Apply our manual tenant scoping
        if ($tenant) {
            $query->where(function ($q) use ($tenant) {
                $q->where('branch_id', $tenant->id)
                  ->orWhereHas('booking', function ($subQ) use ($tenant) {
                      $subQ->where('branch_id', $tenant->id);
                  });
            });
        }
        
        return $query->with(['booking.client', 'booking.service']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payment Details')
                    ->schema([
                        Forms\Components\Select::make('booking_id')
                            ->label('Booking')
                            ->relationship('booking', 'booking_reference')
                            ->getOptionLabelFromRecordUsing(function ($record) {
                                return $record->booking_reference . ' - ' . 
                                       ($record->client->first_name ?? '') . ' ' . 
                                       ($record->client->last_name ?? '') . ' - ' .
                                       ($record->service->name ?? '');
                            })
                            ->searchable(['booking_reference'])
                            ->required()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\Section::make('Booking Information')
                                    ->schema([
                                        Forms\Components\Select::make('client_id')
                                            ->label('Client')
                                            ->relationship('client', 'first_name')
                                            ->getOptionLabelFromRecordUsing(fn ($record) => 
                                                $record->first_name . ' ' . $record->last_name . ' (' . $record->email . ')'
                                            )
                                            ->searchable(['first_name', 'last_name', 'email'])
                                            ->required()
                                            ->preload()
                                            ->createOptionForm([
                                                Forms\Components\Section::make('Client Information')
                                                    ->schema([
                                                        Forms\Components\Grid::make(2)
                                                            ->schema([
                                                                Forms\Components\TextInput::make('first_name')
                                                                    ->label('First Name')
                                                                    ->required()
                                                                    ->maxLength(255),
                                                                    
                                                                Forms\Components\TextInput::make('last_name')
                                                                    ->label('Last Name')
                                                                    ->required()
                                                                    ->maxLength(255),
                                                            ]),
                                                            
                                                        Forms\Components\TextInput::make('phone')
                                                            ->label('Phone Number')
                                                            ->tel()
                                                            ->required()
                                                            ->maxLength(20),
                                                            
                                                        Forms\Components\TextInput::make('email')
                                                            ->label('Email Address')
                                                            ->email()
                                                            ->unique(\App\Models\User::class, 'email')
                                                            ->maxLength(255),
                                                            
                                                        Forms\Components\DatePicker::make('date_of_birth')
                                                            ->label('Date of Birth')
                                                            ->maxDate(today()),
                                                            
                                                        Forms\Components\Select::make('gender')
                                                            ->label('Gender')
                                                            ->options([
                                                                'male' => 'Male',
                                                                'female' => 'Female',
                                                                'other' => 'Other',
                                                                'prefer_not_to_say' => 'Prefer not to say',
                                                            ]),
                                                            
                                                        Forms\Components\Textarea::make('address')
                                                            ->label('Address')
                                                            ->rows(3)
                                                            ->maxLength(500),
                                                            
                                                        Forms\Components\Textarea::make('allergies')
                                                            ->label('Allergies')
                                                            ->placeholder('List any known allergies...')
                                                            ->rows(2)
                                                            ->maxLength(1000),
                                                            
                                                        Forms\Components\Textarea::make('preferences')
                                                            ->label('Preferences')
                                                            ->placeholder('Any special preferences or requirements...')
                                                            ->rows(2)
                                                            ->maxLength(1000),
                                                            
                                                        Forms\Components\Toggle::make('marketing_consent')
                                                            ->label('Marketing Consent')
                                                            ->helperText('Client agrees to receive marketing communications')
                                                            ->default(false),
                                                            
                                                        Forms\Components\Textarea::make('notes')
                                                            ->label('Notes')
                                                            ->rows(2)
                                                            ->maxLength(1000),
                                                    ])
                                            ])
                                            ->createOptionUsing(function (array $data) {
                                                $data['user_type'] = 'client';
                                                $data['password'] = \Hash::make(\Str::random(12)); // Generate random password
                                                
                                                // If no email provided, generate a placeholder email
                                                if (empty($data['email'])) {
                                                    $data['email'] = strtolower($data['first_name'] . '.' . $data['last_name'] . '.' . time() . '@placeholder.local');
                                                }
                                                
                                                // Provide default values for required fields
                                                $data['allergies'] = $data['allergies'] ?? '';
                                                $data['preferences'] = $data['preferences'] ?? '';
                                                $data['marketing_consent'] = $data['marketing_consent'] ?? false;
                                                
                                                $client = \App\Models\User::create($data);
                                                
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Client Created')
                                                    ->body('New client ' . $data['first_name'] . ' ' . $data['last_name'] . ' created successfully')
                                                    ->success()
                                                    ->send();
                                                    
                                                return $client->id;
                                            })
                                            ->createOptionModalHeading('Create New Client'),
                                            
                                        Forms\Components\Select::make('service_id')
                                            ->label('Service')
                                            ->relationship('service', 'name', function ($query) {
                                                $tenant = \Filament\Facades\Filament::getTenant();
                                                if ($tenant) {
                                                    $query->whereHas('branches', function ($q) use ($tenant) {
                                                        $q->where('branches.id', $tenant->id);
                                                    });
                                                }
                                            })
                                            ->getOptionLabelFromRecordUsing(fn ($record) => 
                                                $record->name . ' (KES ' . number_format($record->price, 2) . ')'
                                            )
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function (callable $set, $state) {
                                                if ($state) {
                                                    $service = \App\Models\Service::find($state);
                                                    if ($service) {
                                                        $set('total_amount', $service->price);
                                                    }
                                                }
                                            }),
                                            
                                        Forms\Components\Select::make('staff_id')
                                            ->label('Staff Member')
                                            ->relationship('staff', 'first_name', function ($query) {
                                                $tenant = \Filament\Facades\Filament::getTenant();
                                                if ($tenant) {
                                                    $query->whereHas('branches', function ($q) use ($tenant) {
                                                        $q->where('branches.id', $tenant->id);
                                                    });
                                                }
                                            })
                                            ->getOptionLabelFromRecordUsing(fn ($record) => 
                                                $record->first_name . ' ' . $record->last_name
                                            )
                                            ->required(),
                                            
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\DatePicker::make('appointment_date')
                                                    ->label('Appointment Date')
                                                    ->required()
                                                    ->minDate(today()),
                                                    
                                                Forms\Components\TimePicker::make('start_time')
                                                    ->label('Start Time')
                                                    ->required(),
                                            ]),
                                            
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TimePicker::make('end_time')
                                                    ->label('End Time')
                                                    ->required()
                                                    ->after('start_time'),
                                                    
                                                Forms\Components\TextInput::make('total_amount')
                                                    ->label('Total Amount')
                                                    ->numeric()
                                                    ->prefix('KES')
                                                    ->step(0.01)
                                                    ->required(),
                                            ]),
                                            
                                        Forms\Components\Select::make('payment_method')
                                            ->label('Payment Method')
                                            ->options([
                                                'cash' => 'Cash',
                                                'mpesa' => 'M-Pesa',
                                                'card' => 'Credit/Debit Card',
                                                'bank_transfer' => 'Bank Transfer',
                                            ])
                                            ->default('cash'),
                                            
                                        Forms\Components\Textarea::make('notes')
                                            ->label('Notes')
                                            ->rows(3),
                                    ])
                            ])
                            ->createOptionUsing(function (array $data) {
                                $tenant = \Filament\Facades\Filament::getTenant();
                                $data['branch_id'] = $tenant->id;
                                $data['status'] = 'pending';
                                $data['payment_status'] = 'pending';
                                
                                $booking = \App\Models\Booking::create($data);
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('Booking Created')
                                    ->body('New booking created successfully')
                                    ->success()
                                    ->send();
                                    
                                return $booking->id;
                            })
                            ->createOptionModalHeading('Create New Booking')
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state) {
                                if ($state) {
                                    $booking = \App\Models\Booking::find($state);
                                    if ($booking) {
                                        $set('amount', $booking->total_amount);
                                        $set('payment_method', $booking->payment_method ?? 'cash');
                                    }
                                }
                            }),
                            
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
                                    ->options(function () {
                                        $tenant = \Filament\Facades\Filament::getTenant();
                                        $activeMethods = $tenant?->payment_settings['active_methods'] ?? ['cash', 'mpesa'];
                                        
                                        $allOptions = [
                                            'cash' => 'Cash',
                                            'mpesa' => 'M-Pesa',
                                            'card' => 'Credit/Debit Card',
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
                                    ->required()
                                    ->reactive()
                                    ->native(false)
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('mpesa_prompt')
                                            ->label('M-Pesa Prompt')
                                            ->icon('heroicon-o-device-phone-mobile')
                                            ->color('success')
                                            ->visible(fn (Forms\Get $get) => $get('payment_method') === 'mpesa' && $get('status') === 'pending')
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
                                                                    <p class="text-sm text-gray-500 max-w-xs mx-auto">An STK push has been sent. Please enter your M-Pesa PIN on the handset.</p>
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
                                                            Forms\Components\TextInput::make('payment_ref')->label('Reference Number')->disabled(),
                                                            Forms\Components\TextInput::make('payable_amount')->label('Amount (KES)')->disabled(),
                                                            Forms\Components\TextInput::make('phone_number')->label('M-Pesa Phone Number')->tel()->required(),
                                                        ])
                                                        ->columns(2)
                                                        ->hidden(fn (Forms\Get $get) => $get('stk_initiated')),
                                                    ])
                                            ])
                                            ->mountUsing(function (Forms\ComponentContainer $form, Forms\Get $get, $record) {
                                                $bookingId = $get('booking_id');
                                                $booking = $bookingId ? \App\Models\Booking::find($bookingId) : $record?->booking;
                                                
                                                return $form->fill([
                                                    'client_display_name' => $booking ? ($booking->booking_type === 'walk_in' ? ($booking->customer_name ?? 'Walk-in') : ($booking->client?->name ?? 'Client')) : 'Client',
                                                    'payment_ref' => $get('transaction_reference') ?: ($record?->transaction_reference ?? 'NEW'),
                                                    'payable_amount' => $get('amount'),
                                                    'phone_number' => $booking?->customer_phone ?? $booking?->client?->phone,
                                                    'stk_initiated' => false,
                                                ]);
                                            })
                                            ->modalHeading('M-Pesa Payment')
                                            ->modalWidth('md')
                                            ->modalSubmitActionLabel(fn (Forms\Get $get) => $get('stk_initiated') ? 'Verify Payment' : 'Send STK Push')
                                            ->modalSubmitAction(fn ($action) => $action->color('success'))
                                            ->action(function (array $data, Forms\Set $set, $action) {
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

                                                    $set('stk_initiated', true);
                                                    \Filament\Notifications\Notification::make()
                                                        ->title('STK Push Sent')
                                                        ->body("Routing to {$mpesaType} Number: {$mpesaNumber}")
                                                        ->success()
                                                        ->send();
                                                    $action->halt();
                                                } else {
                                                    \Filament\Notifications\Notification::make()->title('Payment Verified')->success()->send();
                                                }
                                            })
                                    ),
                            ]),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'completed' => 'Completed',
                                        'failed' => 'Failed',
                                        'refunded' => 'Refunded'
                                    ])
                                    ->default('pending')
                                    ->required()
                                    ->native(false),
                                    
                                Forms\Components\DateTimePicker::make('processed_at')
                                    ->label('Processed At')
                                    ->seconds(false),
                            ]),
                    ])->columns(1),
                    
                Forms\Components\Section::make('Transaction Details')
                    ->schema([
                        Forms\Components\TextInput::make('transaction_reference')
                            ->label('Transaction Reference')
                            ->unique(Payment::class, 'transaction_reference', ignoreRecord: true)
                            ->maxLength(100),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('mpesa_checkout_request_id')
                                    ->label('M-Pesa Checkout ID')
                                    ->maxLength(100)
                                    ->visible(fn (Forms\Get $get) => $get('payment_method') === 'mpesa'),
                                    
                                Forms\Components\TextInput::make('mpesa_transaction_id')
                                    ->label('M-Pesa Transaction ID')
                                    ->maxLength(100)
                                    ->visible(fn (Forms\Get $get) => $get('payment_method') === 'mpesa'),
                            ]),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('card_last_four')
                                    ->label('Card Last 4 Digits')
                                    ->maxLength(4)
                                    ->visible(fn (Forms\Get $get) => $get('payment_method') === 'card'),
                                    
                                Forms\Components\TextInput::make('card_brand')
                                    ->label('Card Brand')
                                    ->maxLength(50)
                                    ->visible(fn (Forms\Get $get) => $get('payment_method') === 'card'),
                            ]),
                    ])->columns(1),
                    
                Forms\Components\Section::make('Refund Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('refund_amount')
                                    ->label('Refund Amount')
                                    ->numeric()
                                    ->prefix('KES')
                                    ->step(0.01)
                                    ->visible(fn (Forms\Get $get) => $get('status') === 'refunded'),
                                    
                                Forms\Components\DateTimePicker::make('refunded_at')
                                    ->label('Refunded At')
                                    ->seconds(false)
                                    ->visible(fn (Forms\Get $get) => $get('status') === 'refunded'),
                            ]),
                    ])->columns(1),
                    
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(1),
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
                            'transaction_reference' => 'Reference',
                            'booking.booking_reference' => 'Booking',
                            'booking.client.name' => 'Client',
                            'amount' => 'Amount (KES)',
                            'payment_method' => 'Method',
                            'status' => 'Status',
                            'processed_at' => 'Processed At',
                        ];
                        
                        return $service->exportToCsv($data, $headers, 'Payments_Export_' . now()->format('Y-m-d'));
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
                            'transaction_reference' => 'Reference',
                            'booking.booking_reference' => 'Booking',
                            'amount' => 'Amount',
                            'payment_method' => 'Method',
                            'status' => 'Status',
                            'processed_at' => 'Processed',
                        ];
                        
                        return $service->exportToPdf('reports.generic', [
                            'title' => 'Payment Transaction Report',
                            'data' => $data,
                            'headers' => $headers,
                            'branch_name' => $tenant?->name ?? 'All Branches',
                            'period' => 'All Records',
                        ], 'Payments_Report_' . now()->format('Y-m-d'), 'a4', 'landscape');
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('transaction_reference')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->copyable(),
                    
                Tables\Columns\TextColumn::make('booking.booking_reference')
                    ->label('Booking')
                    ->placeholder('N/A')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => $record->booking_id ? route('filament.admin.resources.bookings.view', ['tenant' => \Filament\Facades\Filament::getTenant(), 'record' => $record->booking_id]) : null)
                    ->color(fn ($record) => $record->booking_id ? 'primary' : 'gray'),
                    
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Client/Customer')
                    ->getStateUsing(function (Payment $record): string {
                        if ($record->booking?->client) {
                            return ($record->booking->client->first_name ?? '') . ' ' . ($record->booking->client->last_name ?? '');
                        }
                        if ($record->booking?->customer_name) {
                            return $record->booking->customer_name;
                        }
                        return $record->customer?->name ?? 'Guest';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('booking.client', function (Builder $query) use ($search) {
                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        })->orWhereHas('booking', function (Builder $query) use ($search) {
                            $query->where('customer_name', 'like', "%{$search}%");
                        })->orWhereHas('customer', function (Builder $query) use ($search) {
                            $query->where('name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('KES')
                    ->sortable()
                    ->weight(FontWeight::Bold),
                    
                Tables\Columns\TextColumn::make('payment_category')
                    ->label('Category')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'membership' => 'primary',
                        'booking' => 'info',
                        'pos' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Method')
                    ->formatStateUsing(fn ($record) => $record->getPaymentMethodDisplayName())
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'mpesa' => 'warning',
                        'card' => 'info',
                        'bank_transfer' => 'gray',
                        default => 'gray'
                    }),
                    
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($record) => $record->getStatusColor()),
                    
                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Processed')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not processed'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_category')
                    ->label('Category')
                    ->options([
                        'booking' => 'Booking',
                        'membership' => 'Membership',
                        'pos' => 'POS Transaction',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded'
                    ])
                    ->multiple(),
                    
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'cash' => 'Cash',
                        'mpesa' => 'M-Pesa',
                        'card' => 'Credit/Debit Card',
                        'bank_transfer' => 'Bank Transfer'
                    ])
                    ->multiple(),
                    
                Tables\Filters\Filter::make('processed_today')
                    ->query(fn (Builder $query): Builder => $query->whereDate('processed_at', today()))
                    ->label('Processed Today'),
                    
                Tables\Filters\Filter::make('amount_range')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('min_amount')
                                    ->label('Min Amount')
                                    ->numeric()
                                    ->prefix('KES'),
                                Forms\Components\TextInput::make('max_amount')
                                    ->label('Max Amount')
                                    ->numeric()
                                    ->prefix('KES'),
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['min_amount'], fn (Builder $query, $amount) => $query->where('amount', '>=', $amount))
                            ->when($data['max_amount'], fn (Builder $query, $amount) => $query->where('amount', '<=', $amount));
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_completed')
                    ->label('Mark Completed')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Payment $record): bool => $record->isPending())
                    ->requiresConfirmation()
                    ->action(function (Payment $record) {
                        $record->markAsCompleted();
                        
                        // Update booking payment status
                        $record->booking->update(['payment_status' => 'completed']);
                        
                        // Trigger real-time dashboard refresh
                        $this->dispatch('payment-updated', [
                            'payment_id' => $record->id,
                            'amount' => $record->amount,
                            'status' => 'completed',
                            'branch_id' => $record->booking->branch_id ?? null,
                        ]);
                    }),
                    
                Tables\Actions\Action::make('mark_failed')
                    ->label('Mark Failed')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Payment $record): bool => $record->isPending())
                    ->requiresConfirmation()
                    ->action(function (Payment $record) {
                        $record->markAsFailed();
                        
                        // Update booking payment status
                        $record->booking->update(['payment_status' => 'failed']);
                    }),
                    
                Tables\Actions\Action::make('refund')
                    ->label('Refund')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (Payment $record): bool => $record->isCompleted() && !$record->isRefunded())
                    ->form([
                        Forms\Components\TextInput::make('refund_amount')
                            ->label('Refund Amount')
                            ->numeric()
                            ->prefix('KES')
                            ->required()
                            ->default(fn (Payment $record) => $record->amount),
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason for Refund')
                            ->required(),
                    ])
                    ->action(function (Payment $record, array $data) {
                        $success = $record->refund($data['refund_amount'], $data['reason']);
                        
                        if ($success) {
                            $record->booking->update(['payment_status' => 'refunded']);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Refund Processed')
                                ->body('The payment has been refunded successfully.')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Refund Failed')
                                ->body('Unable to process refund. Check amount values.')
                                ->danger()
                                ->send();
                        }
                    }),
                    
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Payment Info')
                    ->schema([
                        Infolists\Components\TextEntry::make('transaction_reference')
                            ->label('Reference')
                            ->weight(FontWeight::Bold)
                            ->copyable(),
                            
                        Infolists\Components\TextEntry::make('amount')
                            ->label('Amount')
                            ->money('KES')
                            ->weight(FontWeight::Bold),
                            
                        Infolists\Components\TextEntry::make('payment_category')
                            ->label('Category')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'membership' => 'primary',
                                'booking' => 'info',
                                'pos' => 'success',
                                default => 'gray',
                            }),
                            
                        Infolists\Components\TextEntry::make('payment_method')
                            ->label('Payment Method')
                            ->formatStateUsing(fn ($record) => $record->getPaymentMethodDisplayName())
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'cash' => 'success',
                                'mpesa' => 'warning',
                                'card' => 'info',
                                'bank_transfer' => 'gray',
                                default => 'gray'
                            }),
                            
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn ($record) => $record->getStatusColor()),
                    ]),
                    
                Infolists\Components\Section::make('Booking Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('booking.booking_reference')
                            ->label('Booking Reference')
                            ->copyable(),
                            
                        Infolists\Components\TextEntry::make('booking.client.first_name')
                            ->label('Client')
                            ->formatStateUsing(fn ($record) => 
                                ($record->booking?->client ? (($record->booking->client->first_name ?? '') . ' ' . ($record->booking->client->last_name ?? '')) : ($record->booking?->customer_name ?? 'Guest'))
                            ),
                            
                        Infolists\Components\TextEntry::make('booking.service.name')
                            ->label('Service')
                            ->placeholder('N/A'),
                            
                        Infolists\Components\TextEntry::make('booking.appointment_date')
                            ->label('Appointment Date')
                            ->date()
                            ->placeholder('N/A'),
                    ])->columns(2),
                    
                Infolists\Components\Section::make('Transaction Timeline')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime(),
                                    
                                Infolists\Components\TextEntry::make('processed_at')
                                    ->label('Processed At')
                                    ->dateTime()
                                    ->placeholder('Not processed'),
                                    
                                Infolists\Components\TextEntry::make('refunded_at')
                                    ->label('Refunded At')
                                    ->dateTime()
                                    ->placeholder('Not refunded')
                                    ->visible(fn ($record) => $record->isRefunded()),
                                    
                                Infolists\Components\TextEntry::make('refund_amount')
                                    ->label('Refund Amount')
                                    ->money('KES')
                                    ->visible(fn ($record) => $record->isRefunded()),
                            ]),
                    ]),
                    
                Infolists\Components\Section::make('Additional Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Notes')
                            ->placeholder('No notes')
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        try {
            $tenant = \Filament\Facades\Filament::getTenant();
            
            if (!$tenant || !auth()->check()) {
                return null;
            }
            
            $count = static::getModel()::withoutGlobalScopes()
                ->when($tenant, function($query) use ($tenant) {
                    $query->whereHas('booking', function($q) use ($tenant) {
                        $q->where('branch_id', $tenant->id);
                    });
                })
                ->where('status', 'pending')
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
