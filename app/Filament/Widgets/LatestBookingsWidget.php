<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestBookingsWidget extends BaseWidget
{
    protected static ?string $heading = 'Latest Bookings';
    
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];
    
    protected static ?string $maxHeight = '400px';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()
                    ->with(['client', 'service', 'staff'])
                    ->where('branch_id', \Filament\Facades\Filament::getTenant()?->id)
                    ->latest('created_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('booking_reference')
                    ->label('Reference')
                    ->searchable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->placeholder('N/A'),
                    
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->searchable()
                    ->placeholder('N/A')
                    ->wrap(),
                    
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Staff')
                    ->searchable()
                    ->placeholder('N/A'),
                    
                Tables\Columns\TextColumn::make('appointment_date')
                    ->label('Date')
                    ->date('M j, Y')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Time')
                    ->time('H:i'),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'confirmed',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                        'gray' => 'no-show',
                    ]),
                    
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money('KES')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (Booking $record): string => \App\Filament\Resources\BookingResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading('No bookings yet')
            ->emptyStateDescription('Bookings will appear here as they are created.')
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->striped()
            ->paginated(false);
    }
    
    protected static bool $isLazy = false;
    
    protected static ?string $pollingInterval = '30s';
}