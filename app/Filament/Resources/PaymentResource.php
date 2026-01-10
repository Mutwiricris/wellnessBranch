<?php

namespace App\Filament\Resources;

use App\Domain\Payment\Services\PaymentService;
use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Support\Enums\PaymentStatus;
use App\Support\Enums\PaymentMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'transaction_reference';

    protected static ?string $navigationLabel = 'Payments';

    protected static ?string $modelLabel = 'Payment';

    protected static ?string $pluralModelLabel = 'Payments';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payment Information')
                    ->description('Payment details - payments are created automatically through bookings')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Forms\Components\TextInput::make('transaction_reference')
                            ->label('Transaction Reference')
                            ->disabled()
                            ->columnSpan(1),

                        Forms\Components\Select::make('payment_method')
                            ->label('Payment Method')
                            ->options(PaymentMethod::options())
                            ->disabled()
                            ->columnSpan(1),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(PaymentStatus::options())
                            ->disabled()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->prefix('KES')
                            ->disabled()
                            ->numeric()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('processing_fee')
                            ->label('Processing Fee')
                            ->prefix('KES')
                            ->disabled()
                            ->numeric()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('net_amount')
                            ->label('Net Amount')
                            ->prefix('KES')
                            ->disabled()
                            ->numeric()
                            ->columnSpan(1),

                        Forms\Components\DateTimePicker::make('processed_at')
                            ->label('Processed At')
                            ->disabled()
                            ->columnSpan(1),

                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Created At')
                            ->disabled()
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Booking Details')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Forms\Components\Select::make('booking_id')
                            ->label('Booking')
                            ->relationship('booking', 'booking_reference')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Refund Information')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->schema([
                        Forms\Components\TextInput::make('refund_amount')
                            ->label('Refund Amount')
                            ->prefix('KES')
                            ->disabled()
                            ->numeric()
                            ->columnSpan(1),

                        Forms\Components\DateTimePicker::make('refunded_at')
                            ->label('Refunded At')
                            ->disabled()
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('notes')
                            ->label('Refund Reason / Notes')
                            ->disabled()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn (?Payment $record) => $record && ($record->refund_amount > 0 || $record->refunded_at)),

                Forms\Components\Section::make('Gateway Information')
                    ->icon('heroicon-o-globe-alt')
                    ->schema([
                        Forms\Components\TextInput::make('mpesa_transaction_id')
                            ->label('M-Pesa Transaction ID')
                            ->disabled()
                            ->visible(fn (?Payment $record) => $record?->isMpesaPayment())
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('mpesa_checkout_request_id')
                            ->label('M-Pesa Checkout Request ID')
                            ->disabled()
                            ->visible(fn (?Payment $record) => $record?->isMpesaPayment())
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('card_brand')
                            ->label('Card Brand')
                            ->disabled()
                            ->visible(fn (?Payment $record) => $record?->isCardPayment())
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('card_last_four')
                            ->label('Card Last 4 Digits')
                            ->disabled()
                            ->visible(fn (?Payment $record) => $record?->isCardPayment())
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('authorization_code')
                            ->label('Authorization Code')
                            ->disabled()
                            ->visible(fn (?Payment $record) => $record?->isCardPayment())
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('bank_reference')
                            ->label('Bank Reference')
                            ->disabled()
                            ->visible(fn (?Payment $record) => $record?->isBankTransfer())
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_reference')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Reference copied!')
                    ->weight('medium')
                    ->icon('heroicon-o-hashtag')
                    ->iconColor('primary'),

                Tables\Columns\TextColumn::make('booking.booking_reference')
                    ->label('Booking')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Payment $record) => $record->booking_id
                        ? route('filament.admin.resources.bookings.view', [
                            'tenant' => $record->branch_id,
                            'record' => $record->booking_id
                        ])
                        : null
                    )
                    ->icon('heroicon-o-calendar')
                    ->iconColor('info')
                    ->description(fn (Payment $record) =>
                        $record->booking
                            ? $record->booking->client->first_name . ' ' . $record->booking->client->last_name
                            : null
                    ),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->formatStateUsing(fn (string $state) =>
                        PaymentMethod::from($state)->label()
                    )
                    ->color(fn (string $state) =>
                        PaymentMethod::from($state)->color()
                    )
                    ->icon(fn (Payment $record) =>
                        $record->getPaymentMethodIcon()
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('KES')
                    ->sortable()
                    ->weight('bold')
                    ->color('success')
                    ->description(fn (Payment $record) =>
                        $record->processing_fee > 0
                            ? 'Fee: ' . $record->getFormattedProcessingFee() . ' | Net: ' . $record->getFormattedNetAmount()
                            : null
                    ),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) =>
                        PaymentStatus::from($state)->label()
                    )
                    ->color(fn (string $state) =>
                        PaymentStatus::from($state)->color()
                    )
                    ->icon(fn (string $state) =>
                        PaymentStatus::from($state)->icon()
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Processed')
                    ->dateTime('M d, Y g:i A')
                    ->sortable()
                    ->icon('heroicon-o-clock')
                    ->iconColor('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('refund_amount')
                    ->label('Refunded')
                    ->money('KES')
                    ->sortable()
                    ->color('danger')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->iconColor('danger')
                    ->toggleable()
                    ->description(fn (Payment $record) =>
                        $record->refunded_at
                            ? $record->refunded_at->format('M d, Y')
                            : null
                    ),

                Tables\Columns\TextColumn::make('customer.first_name')
                    ->label('Customer')
                    ->searchable(['first_name', 'last_name'])
                    ->formatStateUsing(fn (Payment $record) =>
                        $record->customer
                            ? $record->customer->first_name . ' ' . $record->customer->last_name
                            : 'N/A'
                    )
                    ->icon('heroicon-o-user')
                    ->iconColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Staff')
                    ->searchable()
                    ->formatStateUsing(fn (Payment $record) =>
                        $record->staff ? $record->staff->name : 'N/A'
                    )
                    ->icon('heroicon-o-user-circle')
                    ->iconColor('success')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Payment Status')
                    ->options(PaymentStatus::options())
                    ->multiple()
                    ->indicator('Status'),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options(PaymentMethod::options())
                    ->multiple()
                    ->indicator('Method'),

                Tables\Filters\Filter::make('amount')
                    ->form([
                        Forms\Components\TextInput::make('amount_from')
                            ->label('Min Amount')
                            ->numeric()
                            ->prefix('KES'),
                        Forms\Components\TextInput::make('amount_to')
                            ->label('Max Amount')
                            ->numeric()
                            ->prefix('KES'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['amount_from'], fn (Builder $query, $amount) =>
                                $query->where('amount', '>=', $amount)
                            )
                            ->when($data['amount_to'], fn (Builder $query, $amount) =>
                                $query->where('amount', '<=', $amount)
                            );
                    })
                    ->indicator('Amount Range'),

                Tables\Filters\Filter::make('processed_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('to')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $query, $date) =>
                                $query->whereDate('processed_at', '>=', $date)
                            )
                            ->when($data['to'], fn (Builder $query, $date) =>
                                $query->whereDate('processed_at', '<=', $date)
                            );
                    })
                    ->indicator('Date Range'),

                Tables\Filters\TernaryFilter::make('refunded')
                    ->label('Refunded Payments')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('refunded_at'),
                        false: fn (Builder $query) => $query->whereNull('refunded_at'),
                    )
                    ->indicator('Refunded'),
            ])
            ->filtersFormColumns(2)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info'),

                    Tables\Actions\Action::make('refund')
                        ->label('Process Refund')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('danger')
                        ->form([
                            Forms\Components\TextInput::make('refund_amount')
                                ->label('Refund Amount')
                                ->numeric()
                                ->prefix('KES')
                                ->required()
                                ->default(fn (Payment $record) => $record->amount)
                                ->helperText(fn (Payment $record) =>
                                    'Maximum refundable: KES ' . number_format($record->amount, 2)
                                ),
                            Forms\Components\Textarea::make('refund_reason')
                                ->label('Reason for Refund')
                                ->required()
                                ->rows(3)
                                ->placeholder('Please explain why this payment is being refunded...'),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading('Process Refund')
                        ->modalDescription('This will refund the payment and update the booking status.')
                        ->modalSubmitActionLabel('Process Refund')
                        ->visible(fn (Payment $record) =>
                            $record->isCompleted() && !$record->isRefunded()
                        )
                        ->action(function (Payment $record, array $data) {
                            try {
                                app(PaymentService::class)->processRefund(
                                    $record->id,
                                    $data['refund_amount'],
                                    $data['refund_reason']
                                );

                                Notification::make()
                                    ->success()
                                    ->title('Refund Processed')
                                    ->body("Refund of KES {$data['refund_amount']} has been processed successfully.")
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Refund Failed')
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
                // No bulk actions for payments - they shouldn't be bulk deleted
            ])
            ->defaultSort('processed_at', 'desc')
            ->poll('30s')
            ->emptyStateHeading('No payments yet')
            ->emptyStateDescription('Payments are created automatically when bookings are processed.')
            ->emptyStateIcon('heroicon-o-banknotes')
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
            'index' => Pages\ListPayments::route('/'),
            'view' => Pages\ViewPayment::route('/{record}'),
            // No create or edit pages - payments created via bookings only
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Payments should only be created through booking workflows
    }

    public static function getNavigationBadge(): ?string
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        if (!$tenant) return null;

        return (string) Payment::where('branch_id', $tenant->id)
            ->where('status', PaymentStatus::PENDING->value)
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Pending payments requiring attention';
    }
}
