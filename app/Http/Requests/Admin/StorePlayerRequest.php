<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StorePlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:players,email'],
            'password' => ['required', 'string', Password::min(8)],
            'league_ids' => ['sometimes', 'array'],
            'league_ids.*' => ['integer', 'exists:leagues,id'],
        ];
    }
}
