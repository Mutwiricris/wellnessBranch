<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StaffResource\Pages;
use App\Filament\Resources\StaffResource\RelationManagers;
use App\Models\Staff;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StaffResource extends Resource
{
    protected static ?string $model = Staff::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationGroup = 'Management';
    
    protected static ?int $navigationSort = 2;
    
    // Override the tenant relationship name since Staff uses many-to-many with branches
    protected static ?string $tenantOwnershipRelationshipName = 'branches';
    
    // Scope staff to only show those assigned to the current branch
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
                Forms\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('experience_years')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->label('Years of Experience'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Professional Details')
                    ->schema([
                        Forms\Components\TagsInput::make('specialties')
                            ->required()
                            ->placeholder('Add specialties (e.g., Hair Styling, Massage, Facial)')
                            ->helperText('Press Enter to add each specialty'),
                        Forms\Components\Textarea::make('bio')
                            ->rows(4)
                            ->placeholder('Professional bio and qualifications'),
                        Forms\Components\TextInput::make('hourly_rate')
                            ->numeric()
                            ->prefix('KES')
                            ->step(0.01)
                            ->placeholder('0.00'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('System Settings')
                    ->schema([
                        Forms\Components\ColorPicker::make('color')
                            ->required()
                            ->default('#3B82F6')
                            ->helperText('Color used in scheduling and calendar views'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'on_leave' => 'On Leave'
                            ])
                            ->default('active')
                            ->required()
                            ->native(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('specialties')
                    ->badge()
                    ->separator(',')
                    ->limit(3),
                Tables\Columns\TextColumn::make('experience_years')
                    ->label('Experience')
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => $state . ' years'),
                Tables\Columns\TextColumn::make('hourly_rate')
                    ->label('Rate')
                    ->money('KES')
                    ->sortable(),
                Tables\Columns\ColorColumn::make('color')
                    ->label('Calendar Color'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'on_leave' => 'warning',
                        default => 'gray'
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'on_leave' => 'On Leave'
                    ]),
                Tables\Filters\Filter::make('has_rate')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('hourly_rate'))
                    ->label('Has Hourly Rate'),
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
            ->defaultSort('name')
            ->recordTitleAttribute('name')
            ->recordUrl(
                fn (Staff $record): string => StaffResource::getUrl('view', ['record' => $record])
            );
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ServicesRelationManager::class,
            RelationManagers\BranchesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaff::route('/'),
            'create' => Pages\CreateStaff::route('/create'),
            'view' => Pages\ViewStaff::route('/{record}'),
            'edit' => Pages\EditStaff::route('/{record}/edit'),
        ];
    }
}