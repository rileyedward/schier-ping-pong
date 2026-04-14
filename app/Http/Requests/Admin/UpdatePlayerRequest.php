<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdatePlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $playerId = $this->route('player')?->id;

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('players', 'email')->ignore($playerId)],
            'password' => ['nullable', 'string', Password::min(8)],
            'league_ids' => ['sometimes', 'array'],
            'league_ids.*' => ['integer', 'exists:leagues,id'],
        ];
    }
}
