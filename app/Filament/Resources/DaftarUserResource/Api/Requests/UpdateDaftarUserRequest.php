<?php

namespace App\Filament\Resources\DaftarUserResource\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDaftarUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
			'username' => 'required',
			'nama' => 'required',
			'email' => 'required',
			'password' => 'required',
			'alamat' => 'required|string',
			'role' => 'required'
		];
    }
}
