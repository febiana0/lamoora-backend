<?php

namespace App\Filament\Resources\DaftarUserResource\Pages;

use App\Filament\Resources\DaftarUserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDaftarUser extends EditRecord
{
    protected static string $resource = DaftarUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
