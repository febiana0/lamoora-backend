<?php
namespace App\Filament\Resources\DaftarUserResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\DaftarUserResource;
use Illuminate\Routing\Router;


class DaftarUserApiService extends ApiService
{
    protected static string | null $resource = DaftarUserResource::class;

    public static function handlers() : array
    {
        return [
            Handlers\CreateHandler::class,
            Handlers\UpdateHandler::class,
            Handlers\DeleteHandler::class,
            Handlers\PaginationHandler::class,
            Handlers\DetailHandler::class
        ];

    }
}
