<?php

namespace App\Filament\Resources\StaffResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServicesRelationManager extends RelationManager
{
    protected static string $relationship = 'services';

    protected static ?string $title = 'Services Offered';

    protected static ?string $label = 'Service';

    protected static ?string $pluralLabel = 'Services';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Service Assignment')
                    ->schema([
                        Forms\Components\Select::make('service_id')
                            ->relationship('services', 'name')
                            ->required()
                            ->disabled(fn ($context) => $context === 'edit'),
                        Forms\Components\Select::make('proficiency_level')
                            ->options([
                                'beginner' => 'Beginner',
                                'intermediate' => 'Intermediate',
                                'advanced' => 'Advanced',
                                'expert' => 'Expert'
                            ])
                            ->default('intermediate')
                            ->required()
                            ->helperText('Staff proficiency level for this service'),
                    ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('category'))
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('KES')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->formatStateUsing(fn (int $state): string => $state . ' min')
                    ->sortable(),
                Tables\Columns\TextColumn::make('pivot.proficiency_level')
                    ->label('Proficiency')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'beginner' => 'gray',
                        'intermediate' => 'info',
                        'advanced' => 'warning',
                        'expert' => 'success',
                        default => 'gray'
                    })
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
                Tables\Filters\SelectFilter::make('proficiency_level')
                    ->options([
                        'beginner' => 'Beginner',
                        'intermediate' => 'Intermediate',
                        'advanced' => 'Advanced',
                        'expert' => 'Expert'
                    ])
                    ->attribute('pivot.proficiency_level'),
                Tables\Filters\Filter::make('active_services')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'active'))
                    ->label('Active Services Only'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Assign Service')
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Service')
                            ->preload(),
                        Forms\Components\Select::make('proficiency_level')
                            ->options([
                                'beginner' => 'Beginner',
                                'intermediate' => 'Intermediate',
                                'advanced' => 'Advanced',
                                'expert' => 'Expert'
                            ])
                            ->default('intermediate')
                            ->required()
                            ->helperText('Staff proficiency level for this service'),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
                        Forms\Components\Select::make('proficiency_level')
                            ->options([
                                'beginner' => 'Beginner',
                                'intermediate' => 'Intermediate',
                                'advanced' => 'Advanced',
                                'expert' => 'Expert'
                            ])
                            ->required()
                            ->helperText('Staff proficiency level for this service'),
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