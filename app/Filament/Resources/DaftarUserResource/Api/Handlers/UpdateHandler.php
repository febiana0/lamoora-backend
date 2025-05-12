<?php
namespace App\Filament\Resources\DaftarUserResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\DaftarUserResource;
use App\Filament\Resources\DaftarUserResource\Api\Requests\UpdateDaftarUserRequest;

class UpdateHandler extends Handlers {
    public static string | null $uri = '/{id}';
    public static string | null $resource = DaftarUserResource::class;

    public static function getMethod()
    {
        return Handlers::PUT;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }


    /**
     * Update DaftarUser
     *
     * @param UpdateDaftarUserRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(UpdateDaftarUserRequest $request)
    {
        $id = $request->route('id');

        $model = static::getModel()::find($id);

        if (!$model) return static::sendNotFoundResponse();

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Update Resource");
    }
}