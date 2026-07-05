<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StaffCommissionResource\Pages;
use App\Models\StaffCommission;
use App\Services\CommissionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

use Filament\Tables\Actions\Action;
use App\Services\ExportService;

class StaffCommissionResource extends Resource
{
    protected static ?string $model = StaffCommission::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Staff Commissions';

    protected static ?string $navigationGroup = 'Staff Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Commission Details')
                    ->schema([
                        Forms\Components\Select::make('staff_id')
                            ->relationship('staff', 'name')
                            ->disabled(),

                        Forms\Components\Select::make('branch_id')
                            ->relationship('branch', 'name')
                            ->disabled(),

                        Forms\Components\Select::make('booking_id')
                            ->relationship('booking', 'id')
                            ->disabled(),

                        Forms\Components\Select::make('commission_structure_id')
                            ->relationship('commissionStructure', 'name')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Financial Details')
                    ->schema([
                        Forms\Components\TextInput::make('service_amount')
                            ->numeric()
                            ->prefix('KES')
                            ->disabled(),

                        Forms\Components\TextInput::make('commission_rate')
                            ->numeric()
                            ->suffix('%')
                            ->disabled(),

                        Forms\Components\TextInput::make('commission_amount')
                            ->numeric()
                            ->prefix('KES')
                            ->disabled(),

                        Forms\Components\TextInput::make('total_earning')
                            ->numeric()
                            ->prefix('KES')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Period & Status')
                    ->schema([
                        Forms\Components\DatePicker::make('period_start')
                            ->disabled(),

                        Forms\Components\DatePicker::make('period_end')
                            ->disabled(),

                        Forms\Components\Select::make('payment_status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'on_hold' => 'On Hold',
                                'ready_for_payment' => 'Ready for Payment',
                                'paid' => 'Paid',
                                'cancelled' => 'Cancelled',
                            ])
                            ->disabled(),

                        Forms\Components\DatePicker::make('hold_until')
                            ->disabled(),

                        Forms\Components\DatePicker::make('payment_date')
                            ->disabled(),

                        Forms\Components\Select::make('payroll_id')
                            ->relationship('payroll', 'payroll_number')
                            ->disabled(),
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
                            'staff.name' => 'Staff Member',
                            'service_amount' => 'Service Amount (KES)',
                            'commission_rate' => 'Rate (%)',
                            'commission_amount' => 'Commission (KES)',
                            'total_earning' => 'Total (KES)',
                            'payment_status' => 'Status',
                        ];
                        
                        return $service->exportToCsv($data, $headers, 'Commissions_Export_' . now()->format('Y-m-d'));
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
                            'staff.name' => 'Staff',
                            'service_amount' => 'Service',
                            'commission_amount' => 'Commission',
                            'total_earning' => 'Total',
                            'payment_status' => 'Status',
                        ];
                        
                        return $service->exportToPdf('reports.generic', [
                            'title' => 'Staff Commission Report',
                            'data' => $data,
                            'headers' => $headers,
                            'branch_name' => $tenant?->name ?? 'All Branches',
                            'period' => 'Commission Cycle',
                        ], 'Commissions_Report_' . now()->format('Y-m-d'), 'a4', 'portrait');
                    }),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $tenant = Filament::getTenant();
                if ($tenant) {
                    $query->where('branch_id', $tenant->id);
                }
            })
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('staff.name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('booking.booking_reference')
                    ->label('Booking Ref')
                    ->placeholder('N/A')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('service_amount')
                    ->money('KES')
                    ->sortable()
                    ->label('Service Amount'),

                Tables\Columns\TextColumn::make('commission_rate')
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('commission_amount')
                    ->money('KES')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'info',
                        'on_hold' => 'gray',
                        'ready_for_payment' => 'success',
                        'paid' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray'
                    })
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('hold_until')
                    ->date()
                    ->sortable()
                    ->toggleable()
                    ->description(fn (StaffCommission $record): string => 
                        $record->hold_until && $record->hold_until->isFuture() 
                            ? 'Held for ' . $record->hold_until->diffForHumans() 
                            : ''
                    ),

                Tables\Columns\TextColumn::make('period_start')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('period_end')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('payment_date')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('payroll.payroll_number')
                    ->label('Payroll')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('staff_id')
                    ->relationship('staff', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Staff Member'),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'on_hold' => 'On Hold',
                        'ready_for_payment' => 'Ready for Payment',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('on_hold')
                    ->label('Currently On Hold')
                    ->query(fn (Builder $query): Builder => $query->where('payment_status', 'on_hold'))
                    ->toggle(),

                Tables\Filters\Filter::make('ready_for_release')
                    ->label('Ready for Release (Hold Expired)')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('payment_status', 'on_hold')
                              ->where('hold_until', '<=', now())
                    )
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (StaffCommission $record): bool => $record->payment_status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (StaffCommission $record) {
                        app(CommissionService::class)->approveCommission($record, Auth::id());
                        
                        Notification::make()
                            ->success()
                            ->title('Commission Approved')
                            ->body('Commission has been approved and placed on hold.')
                            ->send();
                    }),

                Tables\Actions\Action::make('release')
                    ->label('Release Hold')
                    ->icon('heroicon-o-lock-open')
                    ->color('info')
                    ->visible(fn (StaffCommission $record): bool => 
                        $record->payment_status === 'on_hold' && 
                        $record->hold_until && 
                        $record->hold_until->isPast()
                    )
                    ->requiresConfirmation()
                    ->action(function (StaffCommission $record) {
                        $record->update(['payment_status' => 'ready_for_payment']);
                        
                        Notification::make()
                            ->success()
                            ->title('Commission Released')
                            ->body('Commission is now ready for payment.')
                            ->send();
                    }),

                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (StaffCommission $record): bool => !in_array($record->payment_status, ['paid', 'cancelled']))
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Cancellation Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (StaffCommission $record, array $data) {
                        $record->update([
                            'payment_status' => 'cancelled',
                            'approval_notes' => $data['reason'],
                        ]);
                        
                        Notification::make()
                            ->success()
                            ->title('Commission Cancelled')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $commissionService = app(CommissionService::class);
                            $user = Auth::user();
                            
                            foreach ($records as $record) {
                                if ($record->payment_status === 'pending') {
                                    $commissionService->approveCommission($record, $user->id);
                                }
                            }
                            
                            Notification::make()
                                ->success()
                                ->title('Commissions Approved')
                                ->body(count($records) . ' commissions have been approved.')
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('release_selected')
                        ->label('Release Hold')
                        ->icon('heroicon-o-lock-open')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->payment_status === 'on_hold' && $record->hold_until && $record->hold_until->isPast()) {
                                    $record->update(['payment_status' => 'ready_for_payment']);
                                    $count++;
                                }
                            }
                            
                            Notification::make()
                                ->success()
                                ->title('Commissions Released')
                                ->body("{$count} commissions are now ready for payment.")
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('No commissions found')
            ->emptyStateDescription('Commissions are automatically created when bookings are completed.')
            ->emptyStateIcon('heroicon-o-currency-dollar');
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
            'index' => Pages\ListStaffCommissions::route('/'),
            'view' => Pages\ViewStaffCommission::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            $tenant = Filament::getTenant();
            if (!$tenant) {
                return null;
            }

            $count = StaffCommission::where('branch_id', $tenant->id)
                ->where('payment_status', 'pending')
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
