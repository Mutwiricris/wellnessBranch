<?php

namespace App\Filament\Resources;

use App\Domain\Inventory\Services\InventoryService;
use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Products';

    protected static ?string $modelLabel = 'Product';

    protected static ?string $pluralModelLabel = 'Products';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Information')
                    ->description('Basic product details and identification')
                    ->icon('heroicon-o-cube')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Product Name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, ?string $state) =>
                                $set('slug', Str::slug($state))
                            )
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('slug')
                            ->label('URL Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Auto-generated from product name')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Stock Keeping Unit - unique identifier')
                            ->columnSpan(1),

                        Forms\Components\Select::make('type')
                            ->label('Product Type')
                            ->required()
                            ->options([
                                'physical' => 'Physical Product',
                                'service' => 'Service',
                                'digital' => 'Digital Product',
                            ])
                            ->default('physical')
                            ->native(false)
                            ->columnSpan(1),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'draft' => 'Draft',
                            ])
                            ->default('active')
                            ->native(false)
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('short_description')
                            ->label('Short Description')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('description')
                            ->label('Full Description')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pricing')
                    ->description('Product pricing information')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Forms\Components\TextInput::make('base_price')
                            ->label('Selling Price')
                            ->required()
                            ->numeric()
                            ->prefix('KES')
                            ->step(0.01)
                            ->default(0)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('cost_price')
                            ->label('Cost Price')
                            ->required()
                            ->numeric()
                            ->prefix('KES')
                            ->step(0.01)
                            ->default(0)
                            ->helperText('Your cost for this product')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('compare_at_price')
                            ->label('Compare At Price')
                            ->numeric()
                            ->prefix('KES')
                            ->step(0.01)
                            ->helperText('Original price for discounts')
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Inventory')
                    ->description('Stock tracking and inventory settings')
                    ->icon('heroicon-o-archive-box')
                    ->schema([
                        Forms\Components\Toggle::make('track_inventory')
                            ->label('Track Inventory')
                            ->helperText('Enable stock level tracking for this product')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Categories & Organization')
                    ->description('Product categorization and tags')
                    ->icon('heroicon-o-tag')
                    ->schema([
                        Forms\Components\Select::make('categories')
                            ->label('Categories')
                            ->relationship('categories', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('slug')
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->rows(3),
                            ])
                            ->columnSpanFull(),

                        Forms\Components\TagsInput::make('tags')
                            ->label('Product Tags')
                            ->placeholder('Add tags...')
                            ->helperText('Press enter to add each tag')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured Product')
                            ->helperText('Show in featured products section')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first')
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Images')
                    ->description('Product images and media')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        Forms\Components\FileUpload::make('images')
                            ->label('Product Images')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->maxFiles(5)
                            ->imageEditor()
                            ->directory('products')
                            ->helperText('Upload up to 5 images. First image will be the main image.')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Shipping & Physical Details')
                    ->description('Product dimensions and shipping information')
                    ->icon('heroicon-o-truck')
                    ->schema([
                        Forms\Components\Toggle::make('requires_shipping')
                            ->label('Requires Shipping')
                            ->default(true)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('weight')
                            ->label('Weight')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('kg')
                            ->columnSpan(1),

                        Forms\Components\KeyValue::make('dimensions')
                            ->label('Dimensions (cm)')
                            ->keyLabel('Dimension')
                            ->valueLabel('Value')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Publishing')
                    ->description('Publication settings')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Publish Date')
                            ->helperText('Leave empty to publish immediately')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('images')
                    ->label('Image')
                    ->circular()
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=Product&color=7F9CF5&background=EBF4FF')
                    ->getStateUsing(fn (Product $record) => $record->getMainImage()),

                Tables\Columns\TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn (Product $record) => $record->sku),

                Tables\Columns\TextColumn::make('categories.name')
                    ->label('Categories')
                    ->badge()
                    ->color('info')
                    ->limit(2)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('base_price')
                    ->label('Price')
                    ->money('KES')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('cost_price')
                    ->label('Cost')
                    ->money('KES')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('branchInventory')
                    ->label('Stock')
                    ->formatStateUsing(function (Product $record) {
                        $tenant = \Filament\Facades\Filament::getTenant();
                        if (!$tenant) return 'N/A';

                        $inventory = $record->branchInventory()
                            ->where('branch_id', $tenant->id)
                            ->first();

                        return $inventory ? $inventory->quantity_available . ' available' : 'Not stocked';
                    })
                    ->badge()
                    ->color(function (Product $record) {
                        $tenant = \Filament\Facades\Filament::getTenant();
                        if (!$tenant) return 'gray';

                        $inventory = $record->branchInventory()
                            ->where('branch_id', $tenant->id)
                            ->first();

                        if (!$inventory || $inventory->quantity_available == 0) return 'danger';
                        if ($inventory->isLowStock()) return 'warning';
                        return 'success';
                    }),

                Tables\Columns\IconColumn::make('track_inventory')
                    ->label('Tracked')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'draft' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'draft' => 'Draft',
                    ])
                    ->indicator('Status'),

                Tables\Filters\SelectFilter::make('categories')
                    ->relationship('categories', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->indicator('Category'),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured')
                    ->indicator('Featured'),

                Tables\Filters\TernaryFilter::make('track_inventory')
                    ->label('Inventory Tracking')
                    ->indicator('Tracked'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info'),

                    Tables\Actions\EditAction::make()
                        ->color('warning'),

                    Tables\Actions\Action::make('manage_stock')
                        ->label('Manage Stock')
                        ->icon('heroicon-o-archive-box')
                        ->color('primary')
                        ->url(fn (Product $record) =>
                            route('filament.admin.resources.products.manage-inventory', ['record' => $record])
                        ),

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
            ->emptyStateHeading('No products yet')
            ->emptyStateDescription('Create your first product to get started.')
            ->emptyStateIcon('heroicon-o-cube')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Product')
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Product::active()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Active products';
    }
}
