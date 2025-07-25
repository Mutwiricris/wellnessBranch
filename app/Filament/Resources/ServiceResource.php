<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\Service;
use App\Models\ServiceCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    
    protected static ?string $navigationGroup = 'Management';
    
    protected static ?int $navigationSort = 3;
    
    // Override the tenant relationship name since Service uses many-to-many with branches
    protected static ?string $tenantOwnershipRelationshipName = 'branches';
    
    // Scope services to only show those available at the current branch
    public static function getEloquentQuery(): Builder
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        
        return parent::getEloquentQuery()
            ->whereHas('branches', function (Builder $query) use ($tenant) {
                $query->where('branch_id', $tenant->id);
            });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Service Details')
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->options(ServiceCategory::active()->pluck('name', 'id'))
                            ->required()
                            ->native(false)
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(),
                                Forms\Components\TextInput::make('icon')
                                    ->maxLength(255)
                                    ->placeholder('💇'),
                                Forms\Components\TextInput::make('sort_order')
                                    ->numeric()
                                    ->default(1),
                            ]),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->rows(3),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive'
                            ])
                            ->default('active')
                            ->required()
                            ->native(false),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Pricing & Duration')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('KES')
                            ->step(0.01)
                            ->placeholder('0.00'),
                        Forms\Components\TextInput::make('duration_minutes')
                            ->required()
                            ->numeric()
                            ->suffix('minutes')
                            ->default(60)
                            ->step(15)
                            ->helperText('Service duration in minutes'),
                        Forms\Components\TextInput::make('buffer_time_minutes')
                            ->numeric()
                            ->suffix('minutes')
                            ->default(15)
                            ->step(5)
                            ->helperText('Time between appointments'),
                        Forms\Components\TextInput::make('max_advance_booking_days')
                            ->numeric()
                            ->suffix('days')
                            ->default(30)
                            ->helperText('How far in advance can this service be booked'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Service Options')
                    ->schema([
                        Forms\Components\Toggle::make('requires_consultation')
                            ->label('Requires Consultation')
                            ->helperText('Does this service require a consultation first?'),
                        Forms\Components\Toggle::make('is_couple_service')
                            ->label('Couple Service')
                            ->helperText('Can this service accommodate couples?'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->color('primary')
                    ->searchable(),
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
                Tables\Columns\IconColumn::make('requires_consultation')
                    ->boolean()
                    ->label('Consultation'),
                Tables\Columns\IconColumn::make('is_couple_service')
                    ->boolean()
                    ->label('Couple'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray'
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(ServiceCategory::active()->pluck('name', 'id'))
                    ->multiple(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive'
                    ]),
                Tables\Filters\Filter::make('requires_consultation')
                    ->query(fn (Builder $query): Builder => $query->where('requires_consultation', true))
                    ->label('Requires Consultation'),
                Tables\Filters\Filter::make('couple_service')
                    ->query(fn (Builder $query): Builder => $query->where('is_couple_service', true))
                    ->label('Couple Services'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('category_id')
            ->groups([
                Tables\Grouping\Group::make('category.name')
                    ->label('Category')
                    ->collapsible(),
            ])
            ->recordTitleAttribute('name')
            ->recordUrl(
                fn (Service $record): string => ServiceResource::getUrl('view', ['record' => $record])
            );
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\BranchesRelationManager::class,
            RelationManagers\StaffRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'view' => Pages\ViewService::route('/{record}'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}