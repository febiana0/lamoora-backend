<?php

namespace App\Filament\Resources\DaftarUserResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\DaftarUserResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\DaftarUserResource\Api\Transformers\DaftarUserTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = DaftarUserResource::class;


    /**
     * Show DaftarUser
     *
     * @param Request $request
     * @return DaftarUserTransformer
     */
    public function handler(Request $request)
    {
        $id = $request->route('id');
        
        $query = static::getEloquentQuery();

        $query = QueryBuilder::for(
            $query->where(static::getKeyName(), $id)
        )
            ->first();

        if (!$query) return static::sendNotFoundResponse();

        return new DaftarUserTransformer($query);
    }
}
