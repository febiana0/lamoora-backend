<?php

namespace App\Filament\Resources;

use Illuminate\Support\Facades\Storage;
use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label('Penjual')
                ->relationship('user', 'name')  // Menampilkan nama penjual
                ->required(),

            Forms\Components\Select::make('category_id')
                ->label('Kategori')
                ->relationship('category', 'name')  // Menampilkan nama kategori
                ->required(),

            Forms\Components\TextInput::make('name')
                ->label('Nama Produk')
                ->required(),

            Forms\Components\Textarea::make('description')
                ->label('Deskripsi Produk')
                ->required(),

            Forms\Components\TextInput::make('price')
                ->label('Harga')
                ->numeric()
                ->required(),

            
            Forms\Components\FileUpload::make('image')
                ->label('Foto Produk')
                ->image()
                ->directory('products')
                ->disk('public')
                ->visibility('public') 
                ->required(),
            

            Forms\Components\TextInput::make('stock')
                ->label('Stok')
                ->numeric()
                ->default(1)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')
                ->label('ID Produk')
                ->sortable()
                ->searchable(),

            TextColumn::make('name')
                ->label('Nama Produk')
                ->sortable()
                ->searchable(),

            TextColumn::make('category.name')
                ->label('Kategori'),

            TextColumn::make('description')
                ->label('Deskrpsi Produk'),

            ImageColumn::make('image')
                ->label('Foto Produk')
                ->square()
                ->height(50)
                ->width(50)
                ->url(fn ($record) => $record->image ? Storage::url($record->image) : null),
            

            TextColumn::make('price')
                ->label('Harga')
                ->money('IDR'),

            TextColumn::make('stock')
                ->label('Stok'),
        ])
        ->filters([
            // Filter dapat ditambahkan di sini jika diperlukan
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
