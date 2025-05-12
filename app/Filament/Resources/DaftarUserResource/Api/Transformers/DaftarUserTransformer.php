<?php
namespace App\Filament\Resources\DaftarUserResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\DaftarUser;

/**
 * @property DaftarUser $resource
 */
class DaftarUserTransformer extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->resource->toArray();
    }
}
