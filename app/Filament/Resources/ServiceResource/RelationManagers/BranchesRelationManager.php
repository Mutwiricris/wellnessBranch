<?php

namespace App\Filament\Resources\ServiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BranchesRelationManager extends RelationManager
{
    protected static string $relationship = 'branches';

    protected static ?string $title = 'Branch Availability';

    protected static ?string $label = 'Branch';

    protected static ?string $pluralLabel = 'Branches';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Branch Service Settings')
                    ->schema([
                        Forms\Components\Select::make('branch_id')
                            ->relationship('branches', 'name')
                            ->required()
                            ->disabled(fn ($context) => $context === 'edit'),
                        Forms\Components\Toggle::make('is_available')
                            ->label('Service Available')
                            ->default(true)
                            ->required(),
                        Forms\Components\TextInput::make('custom_price')
                            ->label('Branch-Specific Price')
                            ->numeric()
                            ->prefix('KES')
                            ->step(0.01)
                            ->helperText('Leave empty to use default service price'),
                    ])->columns(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('address')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                Tables\Columns\IconColumn::make('pivot.is_available')
                    ->boolean()
                    ->label('Available')
                    ->sortable(),
                Tables\Columns\TextColumn::make('pivot.custom_price')
                    ->label('Custom Price')
                    ->money('KES')
                    ->placeholder('Default')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray'
                    }),
            ])
            ->filters([
                Tables\Filters\Filter::make('available_only')
                    ->query(fn (Builder $query): Builder => $query->wherePivot('is_available', true))
                    ->label('Available Only'),
                Tables\Filters\Filter::make('has_custom_price')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('branch_services.custom_price'))
                    ->label('Has Custom Price'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Add to Branch')
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Branch'),
                        Forms\Components\Toggle::make('is_available')
                            ->label('Service Available')
                            ->default(true),
                        Forms\Components\TextInput::make('custom_price')
                            ->label('Branch-Specific Price')
                            ->numeric()
                            ->prefix('KES')
                            ->step(0.01)
                            ->helperText('Leave empty to use default service price'),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
                        Forms\Components\Toggle::make('is_available')
                            ->label('Service Available')
                            ->required(),
                        Forms\Components\TextInput::make('custom_price')
                            ->label('Branch-Specific Price')
                            ->numeric()
                            ->prefix('KES')
                            ->step(0.01)
                            ->helperText('Leave empty to use default service price'),
                    ]),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }
}