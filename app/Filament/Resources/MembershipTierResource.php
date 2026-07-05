<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MembershipTierResource\Pages;
use App\Models\MembershipTier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MembershipTierResource extends Resource
{
    protected static ?string $model = MembershipTier::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 4;

    protected static ?string $tenantOwnershipRelationshipName = 'branch';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tier Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->disabled(),
                        
                        Forms\Components\Select::make('billing_cycle')
                            ->options([
                                'monthly' => 'Monthly',
                                'quarterly' => 'Quarterly',
                                'yearly' => 'Yearly',
                            ])
                            ->disabled(),

                        Forms\Components\TextInput::make('price')
                            ->prefix('KES')
                            ->disabled(),

                        Forms\Components\Textarea::make('description')
                            ->disabled()
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Discounts & Perks')
                    ->schema([
                        Forms\Components\TextInput::make('product_discount_percentage')
                            ->label('Product Discount (%)')
                            ->disabled(),

                        Forms\Components\TextInput::make('service_discount_percentage')
                            ->label('Service Discount (%)')
                            ->disabled(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Visible to Clients')
                            ->disabled(),
                    ])->columns(3),

                Forms\Components\Section::make('Included Services')
                    ->description('Services included in this membership and their limits.')
                    ->schema([
                        Forms\Components\Repeater::make('tierServices')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('service_id')
                                    ->relationship('service', 'name', fn (Builder $query) => $query->whereHas('branches', fn ($q) => $q->where('branches.id', \Filament\Facades\Filament::getTenant()->id)))
                                    ->disabled(),
                                
                                Forms\Components\Toggle::make('is_unlimited')
                                    ->label('Unlimited Usage')
                                    ->disabled(),

                                Forms\Components\TextInput::make('allocated_quantity')
                                    ->label('Allocated Qty (per cycle)')
                                    ->disabled(),
                            ])
                            ->columns(3)
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('KES')
                    ->sortable(),
                Tables\Columns\TextColumn::make('billing_cycle')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('product_discount_percentage')
                    ->label('Prod. Disc %')
                    ->suffix('%'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('client_memberships_count')
                    ->label('Active Members')
                    ->counts('clientMemberships')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // Read-only, no bulk delete
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Membership Tier Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->weight('bold')
                            ->size('lg'),
                        Infolists\Components\TextEntry::make('price')
                            ->money('KES'),
                        Infolists\Components\TextEntry::make('billing_cycle')
                            ->badge()
                            ->color('info'),
                        Infolists\Components\TextEntry::make('is_active')
                            ->label('Status')
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'danger')
                            ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive'),
                        Infolists\Components\TextEntry::make('description')
                            ->columnSpanFull(),
                    ])->columns(2),

                Infolists\Components\Section::make('Discounts & Perks')
                    ->schema([
                        Infolists\Components\TextEntry::make('product_discount_percentage')
                            ->label('Product Discount')
                            ->suffix('%'),
                        Infolists\Components\TextEntry::make('service_discount_percentage')
                            ->label('Service Discount')
                            ->suffix('%'),
                    ])->columns(2),

                Infolists\Components\Section::make('Included Services')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('tierServices')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('service.name')
                                    ->label('Service'),
                                Infolists\Components\TextEntry::make('is_unlimited')
                                    ->label('Usage')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'warning')
                                    ->formatStateUsing(fn ($state, $record) => $state ? 'Unlimited' : ($record->allocated_quantity . ' per cycle')),
                            ])->columns(2),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMembershipTiers::route('/'),
            'view' => Pages\ViewMembershipTier::route('/{record}'),
        ];
    }
}
