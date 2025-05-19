<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Transaksi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'shipped' => 'Shipped',
                    ])
                    ->required(),

                TextInput::make('total_price')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),

                Repeater::make('items')
                    ->relationship()
                    ->schema([
                        Select::make('product_id')
                            ->relationship('product', 'name')
                            ->required(),

                        TextInput::make('quantity')
                            ->numeric()
                            ->required(),

                        TextInput::make('price')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(),
                        Select::make('shipping')
                            ->label('Jasa Pengiriman')
                            ->options([
                                'jne' => 'JNE',
                                'jnt' => 'J&T',
                                'sicepat' => 'SiCepat',
                                'anteraja' => 'AnterAja',
                                'gosend' => 'GoSend',
                            ])
                            ->required(),
                    ])
                    ->columns(3)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('user.name')->label('User'),
            TextColumn::make('address')->label('Alamat Pengiriman'),
            TextColumn::make('phone')->label('No. Telepon'),
            TextColumn::make('shipping')->label('Ekspedisi'),
            TextColumn::make('total_price')->label('Total')->money('IDR'),
            TextColumn::make('status')->badge(),
            TextColumn::make('created_at')->label('Tanggal')->dateTime(),
        ])
        ->filters([
            //
        ])
        ->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ]);
}

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}
