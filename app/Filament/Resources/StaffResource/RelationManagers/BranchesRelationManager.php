<?php

namespace App\Filament\Resources\StaffResource\RelationManagers;

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

    protected static ?string $title = 'Branch Assignments';

    protected static ?string $label = 'Branch';

    protected static ?string $pluralLabel = 'Branches';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Branch Assignment')
                    ->schema([
                        Forms\Components\Select::make('branch_id')
                            ->relationship('branches', 'name')
                            ->required()
                            ->disabled(fn ($context) => $context === 'edit'),
                        Forms\Components\Textarea::make('working_hours')
                            ->label('Working Hours')
                            ->placeholder('Mon-Fri: 9:00 AM - 5:00 PM')
                            ->helperText('Specify the working hours for this branch'),
                        Forms\Components\Toggle::make('is_primary_branch')
                            ->label('Primary Branch')
                            ->helperText('Mark this as the staff member\'s primary branch'),
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
                Tables\Columns\TextColumn::make('address')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pivot.working_hours')
                    ->label('Working Hours')
                    ->limit(30)
                    ->placeholder('Not specified'),
                Tables\Columns\IconColumn::make('pivot.is_primary_branch')
                    ->boolean()
                    ->label('Primary')
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
                Tables\Filters\Filter::make('primary_only')
                    ->query(fn (Builder $query): Builder => $query->wherePivot('is_primary_branch', true))
                    ->label('Primary Branches Only'),
                Tables\Filters\Filter::make('active_only')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'active'))
                    ->label('Active Branches Only'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Assign to Branch')
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Branch'),
                        Forms\Components\Textarea::make('working_hours')
                            ->label('Working Hours')
                            ->placeholder('Mon-Fri: 9:00 AM - 5:00 PM')
                            ->helperText('Specify the working hours for this branch'),
                        Forms\Components\Toggle::make('is_primary_branch')
                            ->label('Primary Branch')
                            ->helperText('Mark this as the staff member\'s primary branch'),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
                        Forms\Components\Textarea::make('working_hours')
                            ->label('Working Hours')
                            ->placeholder('Mon-Fri: 9:00 AM - 5:00 PM')
                            ->helperText('Specify the working hours for this branch'),
                        Forms\Components\Toggle::make('is_primary_branch')
                            ->label('Primary Branch')
                            ->helperText('Mark this as the staff member\'s primary branch'),
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