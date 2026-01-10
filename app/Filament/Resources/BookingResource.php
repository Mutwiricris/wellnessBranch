<?php

namespace App\Filament\Resources;

use App\Domain\Booking\Services\BookingService;
use App\Domain\Payment\Services\MpesaService;
use App\Domain\Payment\Services\PaymentService;
use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Support\Enums\BookingStatus;
use App\Support\Enums\PaymentMethod;
use App\Support\Enums\PaymentStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Bookings';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'booking_reference';

    protected static ?string $navigationLabel = 'Bookings';

    protected static ?string $modelLabel = 'Booking';

    protected static ?string $pluralModelLabel = 'Bookings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Booking Information')
                    ->description('Select the service, date, and time for this booking')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Forms\Components\TextInput::make('booking_reference')
                            ->label('Booking Reference')
                            ->default(fn() => 'SPA' . now()->format('ymd') . strtoupper(substr(md5(uniqid()), 0, 6)))
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('service_id')
                            ->label('Service')
                            ->relationship('service', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $service = Service::find($state);
                                    if ($service) {
                                        $set('total_amount', $service->price);
                                    }
                                }
                            })
                            ->helperText('Select the service for this booking')
                            ->columnSpan(1),

                        Forms\Components\Select::make('staff_id')
                            ->label('Staff Member')
                            ->relationship('staff', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Assign a staff member to this booking')
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('appointment_date')
                            ->label('Appointment Date')
                            ->required()
                            ->native(false)
                            ->displayFormat('M d, Y')
                            ->minDate(now())
                            ->helperText('Select the date for the appointment')
                            ->columnSpan(1),

                        Forms\Components\TimePicker::make('start_time')
                            ->label('Start Time')
                            ->required()
                            ->seconds(false)
                            ->columnSpan(1),

                        Forms\Components\TimePicker::make('end_time')
                            ->label('End Time')
                            ->required()
                            ->seconds(false)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Amount')
                            ->required()
                            ->numeric()
                            ->prefix('KES')
                            ->helperText('Total cost for this booking')
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Client Information')
                    ->description('Optional - Select or create a client for this booking')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->label('Client')
                            ->relationship('client', 'first_name', fn(Builder $query) =>
                                $query->where('user_type', 'client')
                            )
                            ->searchable(['first_name', 'last_name', 'email', 'phone'])
                            ->getOptionLabelFromRecordUsing(fn (User $record) =>
                                "{$record->first_name} {$record->last_name} - {$record->phone}"
                            )
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('first_name')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                $lastName = $get('last_name') ?? '';
                                                $set('name', trim($state . ' ' . $lastName));
                                            }),
                                        Forms\Components\TextInput::make('last_name')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                $firstName = $get('first_name') ?? '';
                                                $set('name', trim($firstName . ' ' . $state));
                                            }),
                                        Forms\Components\TextInput::make('email')
                                            ->email()
                                            ->maxLength(255)
                                            ->default(fn () => 'noemail_' . \Illuminate\Support\Str::random(10) . '@placeholder.local'),
                                        Forms\Components\TextInput::make('phone')
                                            ->tel()
                                            ->maxLength(255)
                                            ->default('N/A'),
                                        Forms\Components\Hidden::make('name'),
                                        Forms\Components\Hidden::make('user_type')
                                            ->default('client'),
                                        Forms\Components\Hidden::make('password')
                                            ->default(fn () => bcrypt(\Illuminate\Support\Str::random(32))),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Additional Details')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Booking Notes')
                            ->placeholder('Any special requests or notes...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
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
                    ->copyable()
                    ->copyMessage('Reference copied!')
                    ->weight('medium')
                    ->icon('heroicon-o-hashtag')
                    ->iconColor('primary'),

                Tables\Columns\TextColumn::make('client')
                    ->label('Client')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->formatStateUsing(fn (Booking $record) =>
                        $record->client->first_name . ' ' . $record->client->last_name
                    )
                    ->description(fn (Booking $record) => $record->client->phone)
                    ->icon('heroicon-o-user')
                    ->iconColor('gray'),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-sparkles')
                    ->iconColor('info')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Staff')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user-circle')
                    ->iconColor('success'),

                Tables\Columns\TextColumn::make('appointment_date')
                    ->label('Date')
                    ->date('M d, Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->iconColor('warning'),

                Tables\Columns\TextColumn::make('time')
                    ->label('Time')
                    ->formatStateUsing(fn (Booking $record) =>
                        date('g:i A', strtotime($record->start_time)) . ' - ' .
                        date('g:i A', strtotime($record->end_time))
                    )
                    ->icon('heroicon-o-clock')
                    ->iconColor('primary'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) =>
                        BookingStatus::from($state)->label()
                    )
                    ->color(fn (string $state) =>
                        BookingStatus::from($state)->color()
                    )
                    ->icon(fn (string $state) =>
                        BookingStatus::from($state)->icon()
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->formatStateUsing(fn (string $state) =>
                        PaymentStatus::from($state)->label()
                    )
                    ->color(fn (string $state) =>
                        PaymentStatus::from($state)->color()
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money('KES')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Booking Status')
                    ->options(BookingStatus::options())
                    ->multiple()
                    ->indicator('Status'),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Payment Status')
                    ->options(PaymentStatus::options())
                    ->indicator('Payment'),

                Tables\Filters\Filter::make('appointment_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('to')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $query, $date) =>
                                $query->whereDate('appointment_date', '>=', $date)
                            )
                            ->when($data['to'], fn (Builder $query, $date) =>
                                $query->whereDate('appointment_date', '<=', $date)
                            );
                    })
                    ->indicator('Date Range'),

                Tables\Filters\SelectFilter::make('staff_id')
                    ->label('Staff Member')
                    ->relationship('staff', 'name')
                    ->searchable()
                    ->preload()
                    ->indicator('Staff'),

                Tables\Filters\SelectFilter::make('service_id')
                    ->label('Service')
                    ->relationship('service', 'name')
                    ->searchable()
                    ->preload()
                    ->indicator('Service'),
            ])
            ->filtersFormColumns(2)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info'),

                    Tables\Actions\EditAction::make()
                        ->color('warning'),

                    Tables\Actions\Action::make('record_payment')
                        ->label('Record Payment')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('payment_method')
                                ->label('Payment Method')
                                ->options(PaymentMethod::options())
                                ->required()
                                ->default(PaymentMethod::CASH->value)
                                ->live()
                                ->helperText('Select how the customer is paying'),

                            Forms\Components\TextInput::make('amount')
                                ->label('Payment Amount')
                                ->numeric()
                                ->required()
                                ->prefix('KES')
                                ->default(fn (Booking $record) => $record->total_amount)
                                ->helperText(fn (Booking $record) =>
                                    'Booking total: KES ' . number_format($record->total_amount, 2)
                                ),

                            Forms\Components\TextInput::make('mpesa_phone')
                                ->label('M-Pesa Phone Number')
                                ->tel()
                                ->required(fn (Forms\Get $get) => $get('payment_method') === 'mpesa')
                                ->placeholder('e.g., 0712345678 or 254712345678')
                                ->helperText('Enter customer phone number to send STK push')
                                ->visible(fn (Forms\Get $get) => $get('payment_method') === 'mpesa')
                                ->live()
                                ->suffixAction(
                                    Forms\Components\Actions\Action::make('sendStkPush')
                                        ->label('Send STK Push')
                                        ->icon('heroicon-o-paper-airplane')
                                        ->color('success')
                                        ->disabled(fn (Forms\Get $get) => !$get('mpesa_phone') || !$get('amount'))
                                        ->requiresConfirmation()
                                        ->modalHeading('Send M-Pesa STK Push')
                                        ->modalDescription(fn (Forms\Get $get) =>
                                            'Send payment request of KES ' . number_format($get('amount') ?? 0, 2) .
                                            ' to ' . $get('mpesa_phone')
                                        )
                                        ->action(function (Forms\Get $get, Forms\Set $set) {
                                            try {
                                                $mpesaService = app(MpesaService::class);

                                                // Validate phone number
                                                if (!$mpesaService->validatePhoneNumber($get('mpesa_phone'))) {
                                                    Notification::make()
                                                        ->danger()
                                                        ->title('Invalid Phone Number')
                                                        ->body('Please enter a valid Kenyan phone number')
                                                        ->send();
                                                    return;
                                                }

                                                // Send STK push
                                                $response = $mpesaService->stkPush(
                                                    phoneNumber: $get('mpesa_phone'),
                                                    amount: $get('amount'),
                                                    accountReference: 'BKG-' . time(),
                                                    transactionDesc: 'Booking Payment'
                                                );

                                                if ($response['success']) {
                                                    // Store checkout request ID for later matching
                                                    $set('mpesa_checkout_request_id', $response['checkout_request_id']);

                                                    Notification::make()
                                                        ->success()
                                                        ->title('STK Push Sent!')
                                                        ->body('Payment request sent to ' . $get('mpesa_phone') .
                                                               '. Customer should enter M-Pesa PIN on their phone.')
                                                        ->duration(10000)
                                                        ->send();
                                                } else {
                                                    Notification::make()
                                                        ->danger()
                                                        ->title('STK Push Failed')
                                                        ->body($response['message'] ?? 'Failed to send payment request')
                                                        ->send();
                                                }
                                            } catch (\Exception $e) {
                                                Notification::make()
                                                    ->danger()
                                                    ->title('Error')
                                                    ->body($e->getMessage())
                                                    ->send();
                                            }
                                        })
                                ),

                            Forms\Components\Hidden::make('mpesa_checkout_request_id'),

                            Forms\Components\TextInput::make('transaction_reference')
                                ->label('Transaction Reference')
                                ->maxLength(255)
                                ->placeholder('e.g., MPESA123456, AUTH-789012')
                                ->helperText(fn (Forms\Get $get) =>
                                    $get('payment_method') === 'mpesa'
                                        ? 'Optional - Only if you already have the M-Pesa code'
                                        : 'Optional but recommended for online payments'
                                )
                                ->visible(fn (Forms\Get $get) =>
                                    $get('payment_method') &&
                                    !PaymentMethod::from($get('payment_method'))->isInstant()
                                ),

                            Forms\Components\Textarea::make('notes')
                                ->label('Payment Notes')
                                ->rows(3)
                                ->placeholder('Any additional notes about this payment...')
                                ->columnSpanFull(),

                            Forms\Components\Toggle::make('auto_confirm')
                                ->label('Auto-confirm Booking')
                                ->default(true)
                                ->helperText('Automatically confirm the booking after recording payment')
                                ->visible(fn (Forms\Get $get) =>
                                    $get('payment_method') &&
                                    PaymentMethod::from($get('payment_method'))->isInstant()
                                ),
                        ])
                        ->modalHeading('Record Payment')
                        ->modalDescription('Record a payment for this booking')
                        ->modalSubmitActionLabel('Record Payment')
                        ->visible(fn (Booking $record) =>
                            $record->status === BookingStatus::PENDING->value &&
                            $record->payment_status === PaymentStatus::PENDING->value &&
                            !$record->payment
                        )
                        ->action(function (Booking $record, array $data) {
                            DB::beginTransaction();

                            try {
                                // Double-check payment doesn't exist
                                if ($record->payment) {
                                    throw new \Exception('Payment already exists for this booking');
                                }

                                // Get branch ID
                                $branchId = $record->branch_id ?? \Filament\Facades\Filament::getTenant()?->id;
                                if (!$branchId) {
                                    throw new \Exception('Unable to determine branch for this payment');
                                }

                                // Prepare payment data
                                $paymentData = [
                                    'booking_id' => $record->id,
                                    'branch_id' => $branchId,
                                    'amount' => $data['amount'],
                                    'payment_method' => $data['payment_method'],
                                    'transaction_reference' => $data['transaction_reference'] ?? null,
                                    'notes' => $data['notes'] ?? null,
                                    'customer_id' => $record->client_id,
                                    // staff_id is nullable - would need to map user to staff record
                                ];

                                // Add M-Pesa specific data if STK push was sent
                                if ($data['payment_method'] === 'mpesa' && !empty($data['mpesa_checkout_request_id'])) {
                                    $paymentData['mpesa_checkout_request_id'] = $data['mpesa_checkout_request_id'];
                                    // Force status to PROCESSING for M-Pesa STK push
                                    $paymentData['status'] = PaymentStatus::PROCESSING->value;
                                }

                                // Create payment
                                $paymentService = app(PaymentService::class);
                                $payment = $paymentService->createPayment($paymentData);

                                $paymentMethod = PaymentMethod::from($data['payment_method']);

                                // Only auto-confirm for instant payments (not M-Pesa with STK push)
                                $shouldAutoConfirm = ($data['auto_confirm'] ?? false) &&
                                                    $paymentMethod->isInstant() &&
                                                    empty($data['mpesa_checkout_request_id']);

                                // Auto-confirm booking if applicable
                                if ($shouldAutoConfirm) {
                                    $bookingService = app(BookingService::class);
                                    $bookingService->confirmBooking($record->id);
                                }

                                DB::commit();

                                // Show appropriate success notification
                                if (!empty($data['mpesa_checkout_request_id'])) {
                                    Notification::make()
                                        ->success()
                                        ->title('M-Pesa Payment Initiated')
                                        ->body('STK push sent. Payment will be confirmed once customer completes the transaction.')
                                        ->send();
                                } elseif ($shouldAutoConfirm) {
                                    Notification::make()
                                        ->success()
                                        ->title('Payment Recorded & Booking Confirmed')
                                        ->body("Payment of KES " . number_format($data['amount'], 2) . " recorded and booking confirmed successfully.")
                                        ->send();
                                } elseif ($paymentMethod->isInstant()) {
                                    Notification::make()
                                        ->success()
                                        ->title('Payment Recorded')
                                        ->body("Payment of KES " . number_format($data['amount'], 2) . " has been recorded successfully.")
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->success()
                                        ->title('Payment Recorded')
                                        ->body("Payment is being processed. Booking will be confirmed once payment clears.")
                                        ->send();
                                }

                            } catch (\Exception $e) {
                                DB::rollBack();

                                Notification::make()
                                    ->danger()
                                    ->title('Payment Recording Failed')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),

                    Tables\Actions\Action::make('confirm')
                        ->label('Confirm Booking')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Confirm Booking')
                        ->modalDescription('Are you sure you want to confirm this booking? Payment must be completed first.')
                        ->modalSubmitActionLabel('Yes, Confirm')
                        ->visible(fn (Booking $record) =>
                            $record->status === BookingStatus::PENDING->value
                        )
                        ->action(function (Booking $record) {
                            try {
                                app(BookingService::class)->confirmBooking($record->id);

                                Notification::make()
                                    ->success()
                                    ->title('Booking Confirmed!')
                                    ->body('The booking has been confirmed successfully.')
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Confirmation Failed')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),

                    Tables\Actions\Action::make('start')
                        ->label('Start Service')
                        ->icon('heroicon-o-play')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Start Service')
                        ->modalDescription('Mark this service as started?')
                        ->modalSubmitActionLabel('Yes, Start')
                        ->visible(fn (Booking $record) =>
                            $record->status === BookingStatus::CONFIRMED->value
                        )
                        ->action(function (Booking $record) {
                            try {
                                app(BookingService::class)->startBooking($record->id);

                                Notification::make()
                                    ->success()
                                    ->title('Service Started')
                                    ->body('The service has been started.')
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Failed to Start')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),

                    Tables\Actions\Action::make('complete')
                        ->label('Complete Service')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Complete Service')
                        ->modalDescription('Mark this booking as completed?')
                        ->modalSubmitActionLabel('Yes, Complete')
                        ->visible(fn (Booking $record) =>
                            $record->status === BookingStatus::IN_PROGRESS->value
                        )
                        ->action(function (Booking $record) {
                            try {
                                app(BookingService::class)->completeBooking($record->id);

                                Notification::make()
                                    ->success()
                                    ->title('Booking Completed!')
                                    ->body('The booking has been marked as completed.')
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Failed to Complete')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),

                    Tables\Actions\Action::make('cancel')
                        ->label('Cancel Booking')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Cancel Booking')
                        ->modalDescription('Please provide a reason for cancellation.')
                        ->form([
                            Forms\Components\Textarea::make('cancellation_reason')
                                ->label('Cancellation Reason')
                                ->required()
                                ->rows(3)
                                ->placeholder('Please explain why this booking is being cancelled...'),
                        ])
                        ->visible(fn (Booking $record) =>
                            in_array($record->status, [
                                BookingStatus::PENDING->value,
                                BookingStatus::CONFIRMED->value
                            ])
                        )
                        ->action(function (Booking $record, array $data) {
                            try {
                                app(BookingService::class)->cancelBooking(
                                    $record->id,
                                    $data['cancellation_reason']
                                );

                                Notification::make()
                                    ->success()
                                    ->title('Booking Cancelled')
                                    ->body('The booking has been cancelled.')
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Cancellation Failed')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),

                    Tables\Actions\Action::make('no_show')
                        ->label('Mark as No-Show')
                        ->icon('heroicon-o-user-minus')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Mark as No-Show')
                        ->modalDescription('This client did not show up for their appointment?')
                        ->modalSubmitActionLabel('Yes, No-Show')
                        ->visible(fn (Booking $record) =>
                            $record->status === BookingStatus::CONFIRMED->value
                        )
                        ->action(function (Booking $record) {
                            try {
                                app(BookingService::class)->markAsNoShow($record->id);

                                Notification::make()
                                    ->warning()
                                    ->title('Marked as No-Show')
                                    ->body('The booking has been marked as no-show.')
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Failed')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),
                ])
                ->label('Actions')
                ->icon('heroicon-m-ellipsis-vertical')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('appointment_date', 'desc')
            ->poll('30s')
            ->emptyStateHeading('No bookings yet')
            ->emptyStateDescription('Create your first booking to get started.')
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create Booking')
                    ->icon('heroicon-o-plus'),
            ])
            ->striped();
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
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        if (!$tenant) return null;

        return (string) Booking::where('branch_id', $tenant->id)
            ->where('appointment_date', now()->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Today\'s pending and confirmed bookings';
    }
}
