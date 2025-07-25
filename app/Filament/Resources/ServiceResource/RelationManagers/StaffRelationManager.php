<?php

namespace App\Filament\Resources\ServiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StaffRelationManager extends RelationManager
{
    protected static string $relationship = 'staff';

    protected static ?string $title = 'Qualified Staff';

    protected static ?string $label = 'Staff Member';

    protected static ?string $pluralLabel = 'Staff Members';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Staff Service Assignment')
                    ->schema([
                        Forms\Components\Select::make('staff_id')
                            ->relationship('staff', 'name')
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
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
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
                Tables\Columns\TextColumn::make('experience_years')
                    ->label('Experience')
                    ->formatStateUsing(fn (int $state): string => $state . ' years')
                    ->sortable(),
                Tables\Columns\TextColumn::make('hourly_rate')
                    ->money('KES')
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
                Tables\Filters\Filter::make('active_only')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'active'))
                    ->label('Active Staff Only'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Assign Staff')
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Staff Member')
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
