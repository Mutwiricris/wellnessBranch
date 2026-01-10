<?php

namespace App\Filament\Resources;

use App\Domain\Staff\Services\StaffService;
use App\Filament\Resources\StaffResource\Pages;
use App\Models\Staff;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class StaffResource extends Resource
{
    protected static ?string $model = Staff::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Staff Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Staff Members';

    protected static ?string $modelLabel = 'Staff Member';

    protected static ?string $pluralModelLabel = 'Staff Members';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Personal Information')
                    ->description('Basic staff member information')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255)
                            ->columnSpan(1),

                        Forms\Components\FileUpload::make('profile_image')
                            ->label('Profile Photo')
                            ->image()
                            ->imageEditor()
                            ->directory('staff-profiles')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('bio')
                            ->label('Biography')
                            ->rows(3)
                            ->placeholder('Brief description about the staff member...')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Professional Details')
                    ->description('Experience and rate information')
                    ->icon('heroicon-o-briefcase')
                    ->schema([
                        Forms\Components\TextInput::make('experience_years')
                            ->label('Years of Experience')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->suffix('years')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('hourly_rate')
                            ->label('Hourly Rate')
                            ->numeric()
                            ->prefix('KES')
                            ->step(0.01)
                            ->columnSpan(1),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'on_leave' => 'On Leave',
                            ])
                            ->default('active')
                            ->native(false)
                            ->columnSpan(1),

                        Forms\Components\ColorPicker::make('color')
                            ->label('Calendar Color')
                            ->helperText('Color used to identify this staff member in the calendar')
                            ->columnSpan(1),

                        Forms\Components\TagsInput::make('specialties')
                            ->label('Specialties')
                            ->placeholder('Add specialties...')
                            ->helperText('Press enter to add each specialty')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Services & Branches')
                    ->description('Assign services and branches')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        Forms\Components\Select::make('services')
                            ->label('Services')
                            ->relationship('services', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Services this staff member can perform')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('branches')
                            ->label('Working Branches')
                            ->relationship('branches', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Branches where this staff member works')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('profile_image')
                    ->label('Photo')
                    ->circular()
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=Staff&color=7F9CF5&background=EBF4FF'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn (Staff $record) => $record->email),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->icon('heroicon-o-phone')
                    ->iconColor('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('services')
                    ->label('Services')
                    ->badge()
                    ->formatStateUsing(fn (Staff $record) =>
                        $record->services->pluck('name')->take(3)->implode(', ')
                    )
                    ->color('info')
                    ->description(fn (Staff $record) =>
                        $record->services->count() > 3
                            ? '+' . ($record->services->count() - 3) . ' more'
                            : null
                    ),

                Tables\Columns\TextColumn::make('branches')
                    ->label('Branches')
                    ->formatStateUsing(fn (Staff $record) =>
                        $record->branches->pluck('name')->implode(', ')
                    )
                    ->icon('heroicon-o-building-storefront')
                    ->iconColor('success')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('experience_years')
                    ->label('Experience')
                    ->formatStateUsing(fn (int $state) => $state . ' yrs')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('hourly_rate')
                    ->label('Rate')
                    ->money('KES')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst(str_replace('_', ' ', $state)))
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'on_leave' => 'warning',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'active' => 'heroicon-o-check-circle',
                        'inactive' => 'heroicon-o-x-circle',
                        'on_leave' => 'heroicon-o-clock',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->sortable(),

                Tables\Columns\ColorColumn::make('color')
                    ->label('Calendar')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'on_leave' => 'On Leave',
                    ])
                    ->indicator('Status'),

                Tables\Filters\SelectFilter::make('services')
                    ->label('Service')
                    ->relationship('services', 'name')
                    ->searchable()
                    ->preload()
                    ->indicator('Service'),

                Tables\Filters\SelectFilter::make('branches')
                    ->label('Branch')
                    ->relationship('branches', 'name')
                    ->searchable()
                    ->preload()
                    ->indicator('Branch'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info'),

                    Tables\Actions\EditAction::make()
                        ->color('warning'),

                    Tables\Actions\Action::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Staff $record) => $record->status !== 'active')
                        ->action(function (Staff $record) {
                            app(StaffService::class)->activateStaff($record->id);

                            Notification::make()
                                ->success()
                                ->title('Staff Activated')
                                ->body("{$record->name} has been activated.")
                                ->send();
                        }),

                    Tables\Actions\Action::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (Staff $record) => $record->status === 'active')
                        ->action(function (Staff $record) {
                            app(StaffService::class)->deactivateStaff($record->id);

                            Notification::make()
                                ->warning()
                                ->title('Staff Deactivated')
                                ->body("{$record->name} has been deactivated.")
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make(),
                ])
                ->label('Actions')
                ->icon('heroicon-m-ellipsis-vertical')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name')
            ->emptyStateHeading('No staff members yet')
            ->emptyStateDescription('Create your first staff member to get started.')
            ->emptyStateIcon('heroicon-o-user-group')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Staff Member')
                    ->icon('heroicon-o-plus'),
            ])
            ->striped();
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
            'index' => Pages\ListStaff::route('/'),
            'create' => Pages\CreateStaff::route('/create'),
            'view' => Pages\ViewStaff::route('/{record}'),
            'edit' => Pages\EditStaff::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        if (!$tenant) return null;

        return (string) Staff::whereHas('branches', function ($q) use ($tenant) {
            $q->where('branches.id', $tenant->id);
        })->where('status', 'active')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Active staff members at this branch';
    }
}
