<?php
namespace App\Filament\Resources\DaftarUserResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\DaftarUserResource;
use App\Filament\Resources\DaftarUserResource\Api\Requests\CreateDaftarUserRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = DaftarUserResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create DaftarUser
     *
     * @param CreateDaftarUserRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateDaftarUserRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}