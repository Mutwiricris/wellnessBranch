<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentTransactionsWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Transactions';
    
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];
    
    protected static ?string $maxHeight = '400px';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Payment::query()
                    ->with(['booking.client', 'booking.service'])
                    ->whereHas('booking', function (Builder $query) {
                        $query->where('branch_id', \Filament\Facades\Filament::getTenant()?->id);
                    })
                    ->latest('created_at')
                    ->limit(15)
            )
            ->columns([
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Transaction ID')
                    ->searchable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary')
                    ->limit(15),
                    
                Tables\Columns\TextColumn::make('booking.client.name')
                    ->label('Client')
                    ->searchable()
                    ->placeholder('N/A')
                    ->wrap(),
                    
                Tables\Columns\TextColumn::make('booking.service.name')
                    ->label('Service')
                    ->searchable()
                    ->placeholder('N/A')
                    ->wrap()
                    ->limit(20),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('KES')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
                    
                Tables\Columns\BadgeColumn::make('payment_method')
                    ->label('Method')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'cash' => 'Cash',
                        'card' => 'Card',
                        'mobile_money' => 'M-Pesa',
                        'bank_transfer' => 'Bank Transfer',
                        'gift_voucher' => 'Gift Voucher',
                        'discount_coupon' => 'Coupon',
                        default => ucfirst($state)
                    })
                    ->colors([
                        'success' => 'cash',
                        'primary' => 'card',
                        'warning' => 'mobile_money',
                        'info' => 'bank_transfer',
                        'secondary' => 'gift_voucher',
                        'danger' => 'discount_coupon',
                    ]),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger' => 'failed',
                        'gray' => 'refunded',
                    ]),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($state): string => $state->format('F j, Y \a\t g:i A')),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (Payment $record): string => \App\Filament\Resources\PaymentResource::getUrl('view', ['record' => $record])),
                    
                Tables\Actions\Action::make('receipt')
                    ->label('Receipt')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->action(function (Payment $record) {
                        // Generate receipt PDF logic here
                        return redirect()->route('payment.receipt', $record);
                    })
                    ->visible(fn (Payment $record) => $record->status === 'completed'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'cash' => 'Cash',
                        'card' => 'Card',
                        'mobile_money' => 'M-Pesa',
                        'bank_transfer' => 'Bank Transfer',
                        'gift_voucher' => 'Gift Voucher',
                        'discount_coupon' => 'Coupon',
                    ]),
                    
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ]),
            ])
            ->emptyStateHeading('No transactions yet')
            ->emptyStateDescription('Payment transactions will appear here as they are processed.')
            ->emptyStateIcon('heroicon-o-banknotes')
            ->striped()
            ->paginated(false)
            ->defaultSort('created_at', 'desc');
    }
    
    protected static bool $isLazy = false;
    
    protected static ?string $pollingInterval = '15s';
}