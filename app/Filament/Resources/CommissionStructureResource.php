<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommissionStructureResource\Pages;
use App\Models\CommissionStructure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class CommissionStructureResource extends Resource
{
    protected static ?string $model = CommissionStructure::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Commission Structures';

    protected static ?string $navigationGroup = 'Staff Management';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Select::make('branch_id')
                            ->relationship('branch', 'name')
                            ->disabled(),

                        Forms\Components\TextInput::make('name')
                            ->disabled(),

                        Forms\Components\Textarea::make('description')
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Commission Configuration')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->options([
                                'percentage' => 'Percentage',
                                'fixed' => 'Fixed Amount',
                                'tiered' => 'Tiered (Based on Revenue)',
                                'hybrid' => 'Hybrid (Percentage + Fixed)',
                            ])
                            ->disabled(),

                        Forms\Components\Select::make('applies_to')
                            ->options([
                                'service' => 'Services Only',
                                'product' => 'Products Only',
                                'package' => 'Packages Only',
                                'all' => 'All (Services, Products & Packages)',
                            ])
                            ->disabled(),

                        Forms\Components\TextInput::make('rules.percentage')
                            ->label('Commission Percentage')
                            ->suffix('%')
                            ->disabled(),

                        Forms\Components\TextInput::make('rules.fixed_amount')
                            ->label('Fixed Commission Amount')
                            ->prefix('KES')
                            ->disabled(),

                        Forms\Components\Repeater::make('rules.tiers')
                            ->label('Commission Tiers')
                            ->schema([
                                Forms\Components\TextInput::make('min_amount')
                                    ->label('Minimum Revenue')
                                    ->prefix('KES')
                                    ->disabled(),

                                Forms\Components\TextInput::make('max_amount')
                                    ->label('Maximum Revenue')
                                    ->prefix('KES')
                                    ->disabled(),

                                Forms\Components\TextInput::make('percentage')
                                    ->label('Commission %')
                                    ->suffix('%')
                                    ->disabled(),
                            ])
                            ->columns(3)
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('rules.min_transaction_amount')
                            ->label('Minimum Transaction Amount')
                            ->prefix('KES')
                            ->disabled()
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $tenant = Filament::getTenant();
                if ($tenant) {
                    $query->where('branch_id', $tenant->id);
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'percentage' => 'success',
                        'fixed' => 'info',
                        'tiered' => 'warning',
                        'hybrid' => 'primary',
                        default => 'gray'
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('applies_to')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('assignments_count')
                    ->label('Staff Assigned')
                    ->counts('assignments')
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'percentage' => 'Percentage',
                        'fixed' => 'Fixed',
                        'tiered' => 'Tiered',
                        'hybrid' => 'Hybrid',
                    ]),

                Tables\Filters\SelectFilter::make('applies_to')
                    ->options([
                        'service' => 'Services',
                        'product' => 'Products',
                        'package' => 'Packages',
                        'all' => 'All',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All structures')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // Read-only bulk actions
            ])
            ->emptyStateHeading('No commission structures found')
            ->emptyStateDescription('Commission structures are configured at the head office/director level.')
            ->emptyStateIcon('heroicon-o-calculator');
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
            'index' => Pages\ListCommissionStructures::route('/'),
            'view' => Pages\ViewCommissionStructure::route('/{record}'),
        ];
    }
}
