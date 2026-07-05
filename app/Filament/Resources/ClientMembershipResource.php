<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientMembershipResource\Pages;
use App\Models\ClientMembership;
use App\Models\MembershipTier;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Facades\Filament;

class ClientMembershipResource extends Resource
{
    protected static ?string $model = ClientMembership::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 5;

    protected static ?string $tenantOwnershipRelationshipName = 'branch';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Subscription Details')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Client')
                            ->relationship('client', 'name', fn (Builder $query) => $query->where('user_type', 'client'))
                            ->required()
                            ->searchable()
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
                                        Forms\Components\Hidden::make('user_type')
                                            ->default('client'),
                                    ])
                            ]),

                        Forms\Components\Select::make('membership_tier_id')
                            ->label('Membership Tier')
                            ->relationship('membershipTier', 'name', fn (Builder $query) => $query->where('branch_id', Filament::getTenant()->id))
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $tier = MembershipTier::find($state);
                                    if ($tier) {
                                        $set('next_billing_date', match($tier->billing_cycle) {
                                            'monthly' => now()->addMonth(),
                                            'quarterly' => now()->addMonths(3),
                                            'yearly' => now()->addYear(),
                                            default => now()->addMonth(),
                                        });
                                        $set('total_paid', $tier->price);
                                    }
                                }
                            }),

                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'active' => 'Active',
                                'canceled' => 'Canceled',
                                'expired' => 'Expired',
                                'past_due' => 'Past Due',
                            ])
                            ->required()
                            ->default('pending')
                            ->reactive(),

                        Forms\Components\DatePicker::make('start_date')
                            ->required()
                            ->default(now()),

                        Forms\Components\DatePicker::make('next_billing_date')
                            ->required(),

                        Forms\Components\DatePicker::make('end_date'),

                        Forms\Components\Toggle::make('auto_renew')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Financials & Cancellation')
                    ->schema([
                        Forms\Components\TextInput::make('total_paid')
                            ->numeric()
                            ->prefix('KES')
                            ->default(0)
                            ->hintAction(
                                Forms\Components\Actions\Action::make('mpesa_prompt_hint')
                                    ->label('Send M-Pesa Prompt')
                                    ->icon('heroicon-m-device-phone-mobile')
                                    ->color('success')
                                    ->visible(fn (Forms\Get $get) => $get('status') === 'pending')
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
                                                            <p class="text-sm text-gray-500 max-w-xs mx-auto">An STK push has been sent. Once the client enters their PIN, click "Verify & Activate".</p>
                                                        </div>
                                                    ')),
                                                Forms\Components\Group::make([
                                                    Forms\Components\TextInput::make('phone_number')->label('M-Pesa Number')->tel()->required(),
                                                ])
                                                ->hidden(fn (Forms\Get $get) => $get('stk_initiated')),
                                            ])
                                    ])
                                    ->mountUsing(fn (Forms\ComponentContainer $form, Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) => $form->fill([
                                        'phone_number' => $record?->client?->phone ?? \App\Models\User::find($get('user_id'))?->phone,
                                        'stk_initiated' => false,
                                    ]))
                                    ->modalHeading('M-Pesa Membership Payment')
                                    ->modalWidth('md')
                                    ->modalSubmitActionLabel(fn (Forms\Get $get) => $get('stk_initiated') ? 'Verify & Activate' : 'Send STK Push')
                                    ->action(function (array $data, Forms\Set $set, $action, Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) {
                                        if (!$data['stk_initiated']) {
                                            if (!$record && (!$get('user_id') || !$get('membership_tier_id'))) {
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Missing Information')
                                                    ->body('Please select a client and membership tier first.')
                                                    ->danger()
                                                    ->send();
                                                $action->halt();
                                            }
                                            $set('stk_initiated', true);
                                            $action->halt();
                                        } else {
                                            $set('status', 'active');
                                            $tierId = $get('membership_tier_id');
                                            if ($tierId) {
                                                $tier = \App\Models\MembershipTier::find($tierId);
                                                if ($tier) {
                                                    $set('total_paid', (float)$tier->price);
                                                    
                                                    if ($record) {
                                                        app(\App\Services\MembershipService::class)->recordPayment(
                                                            $record,
                                                            (float)$tier->price,
                                                            'mpesa',
                                                            'STK-VERIFIED'
                                                        );
                                                    }
                                                }
                                            }

                                            \Filament\Notifications\Notification::make()
                                                ->title($record ? 'Payment Verified' : 'Payment Prepared')
                                                ->body($record ? 'Membership status has been updated to Active in the form.' : 'Save the membership to finalize activation.')
                                                ->success()
                                                ->send();
                                        }
                                    })
                            )
                            ->hintAction(
                                Forms\Components\Actions\Action::make('confirm_manual_payment')
                                    ->label('Confirm Cash Payment')
                                    ->icon('heroicon-m-check-badge')
                                    ->color('info')
                                    ->visible(fn (Forms\Get $get) => $get('status') === 'pending')
                                    ->requiresConfirmation()
                                    ->action(function (Forms\Set $set, Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) {
                                        $set('status', 'active');
                                        $tierId = $get('membership_tier_id');
                                        if ($tierId) {
                                            $tier = \App\Models\MembershipTier::find($tierId);
                                            if ($tier) {
                                                $set('total_paid', (float)$tier->price);
                                                
                                                if ($record) {
                                                    app(\App\Services\MembershipService::class)->recordPayment(
                                                        $record,
                                                        (float)$tier->price,
                                                        'cash',
                                                        'MANUAL-CONFIRM'
                                                    );
                                                }
                                            }
                                        }

                                        \Filament\Notifications\Notification::make()
                                            ->title($record ? 'Manual Payment Confirmed' : 'Payment Prepared')
                                            ->body($record ? 'Membership status updated to Active.' : 'Save the membership to finalize payment.')
                                            ->success()
                                            ->send();
                                    })
                            ),

                        Forms\Components\DateTimePicker::make('canceled_at')
                            ->disabled(),

                        Forms\Components\Textarea::make('cancellation_reason')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('membershipTier.name')
                    ->label('Tier')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'canceled' => 'danger',
                        'past_due' => 'danger',
                        'expired' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('next_billing_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_paid')
                    ->money('KES')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'pending' => 'Pending',
                        'canceled' => 'Canceled',
                        'expired' => 'Expired',
                        'past_due' => 'Past Due',
                    ]),
                Tables\Filters\SelectFilter::make('membership_tier_id')
                    ->label('Tier')
                    ->relationship('membershipTier', 'name', fn (Builder $query) => $query->where('branch_id', Filament::getTenant()->id)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('confirm_payment')
                    ->label('Confirm Payment')
                    ->modalHeading('Manual Payment Confirmation')
                    ->modalDescription('Confirm that the client has paid for this membership via cash or other manual methods. This will activate the membership.')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (ClientMembership $record): bool => $record->status === 'pending')
                    ->form([
                        Forms\Components\TextInput::make('amount_paid')
                            ->label('Amount Paid')
                            ->numeric()
                            ->prefix('KES')
                            ->default(fn (ClientMembership $record) => $record->membershipTier->price)
                            ->required(),
                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'cash' => 'Cash',
                                'bank_transfer' => 'Bank Transfer',
                                'card' => 'Card',
                            ])
                            ->default('cash')
                            ->required(),
                    ])
                    ->action(function (ClientMembership $record, array $data) {
                        $record->update([
                            'status' => 'active',
                            'total_paid' => $record->total_paid + $data['amount_paid'],
                        ]);

                        app(\App\Services\MembershipService::class)->recordPayment(
                            $record,
                            (float)$data['amount_paid'],
                            $data['payment_method'],
                            'TABLE-CONFIRM'
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('Payment Confirmed')
                            ->body("Membership for {$record->client->name} is now active.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('mpesa_prompt')
                    ->label('M-Pesa Prompt')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->color('info')
                    ->visible(fn (ClientMembership $record): bool => $record->status === 'pending')
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
                                            <p class="text-sm text-gray-500 max-w-xs mx-auto">An STK push has been sent. Once the client enters their PIN, click "Verify & Activate" below.</p>
                                        </div>
                                    ')),
                                Forms\Components\Group::make([
                                    Forms\Components\TextInput::make('client_name')->label('Client')->disabled(),
                                    Forms\Components\TextInput::make('payable_amount')->label('Amount (KES)')->disabled(),
                                    Forms\Components\TextInput::make('phone_number')->label('M-Pesa Number')->tel()->required(),
                                ])
                                ->columns(2)
                                ->hidden(fn (Forms\Get $get) => $get('stk_initiated')),
                            ])
                    ])
                    ->mountUsing(fn (Forms\ComponentContainer $form, ClientMembership $record) => $form->fill([
                        'client_name' => $record->client?->name,
                        'payable_amount' => $record->membershipTier?->price,
                        'phone_number' => $record->client?->phone,
                        'stk_initiated' => false,
                    ]))
                    ->modalHeading('M-Pesa Activation')
                    ->modalWidth('md')
                    ->modalSubmitActionLabel(fn (Forms\Get $get) => $get('stk_initiated') ? 'Verify & Activate' : 'Send STK Push')
                    ->action(function (ClientMembership $record, array $data, Forms\Set $set, $action) {
                        if (!$data['stk_initiated']) {
                            $set('stk_initiated', true);
                            $action->halt();
                        } else {
                            $record->update([
                                'status' => 'active',
                                'total_paid' => $record->total_paid + $record->membershipTier->price,
                            ]);

                            app(\App\Services\MembershipService::class)->recordPayment(
                                $record,
                                (float)$record->membershipTier->price,
                                'mpesa',
                                'STK-TABLE-VERIFIED'
                            );

                            \Filament\Notifications\Notification::make()
                                ->title('Membership Activated')
                                ->body("M-Pesa payment verified for {$record->client->name}.")
                                ->success()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('cancel')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->hidden(fn ($record) => $record->status === 'canceled')
                    ->form([
                        Forms\Components\Textarea::make('cancellation_reason')
                            ->required(),
                    ])
                    ->action(function (ClientMembership $record, array $data) {
                        $record->update([
                            'status' => 'canceled',
                            'canceled_at' => now(),
                            'cancellation_reason' => $data['cancellation_reason'],
                            'auto_renew' => false,
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Subscription Overview')
                    ->schema([
                        Infolists\Components\TextEntry::make('client.name')
                            ->label('Client')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('membershipTier.name')
                            ->label('Tier'),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'pending' => 'warning',
                                'canceled' => 'danger',
                                'past_due' => 'danger',
                                'expired' => 'gray',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('start_date')
                            ->date(),
                        Infolists\Components\TextEntry::make('next_billing_date')
                            ->date(),
                        Infolists\Components\TextEntry::make('end_date')
                            ->date()
                            ->placeholder('Ongoing'),
                        Infolists\Components\IconEntry::make('auto_renew')
                            ->boolean(),
                    ])->columns(2),

                Infolists\Components\Section::make('Financial Status')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_paid')
                            ->money('KES')
                            ->weight('bold')
                            ->size('lg'),
                        Infolists\Components\TextEntry::make('membershipTier.price')
                            ->label('Cycle Price')
                            ->money('KES'),
                    ])->columns(2),

                Infolists\Components\Section::make('Cancellation Info')
                    ->schema([
                        Infolists\Components\TextEntry::make('canceled_at')
                            ->dateTime()
                            ->placeholder('N/A'),
                        Infolists\Components\TextEntry::make('cancellation_reason')
                            ->placeholder('N/A'),
                    ])->columns(2)
                    ->visible(fn ($record) => $record->status === 'canceled'),
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
            'index' => Pages\ListClientMemberships::route('/'),
            'create' => Pages\CreateClientMembership::route('/create'),
            'view' => Pages\ViewClientMembership::route('/{record}'),
            'edit' => Pages\EditClientMembership::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
