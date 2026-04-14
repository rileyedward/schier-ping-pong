<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMatchupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'player_one_id' => ['required', 'integer', 'exists:players,id', 'different:player_two_id'],
            'player_two_id' => ['required', 'integer', 'exists:players,id'],
            'scheduled_for' => ['required', 'date'],
            'best_of' => ['required', Rule::in([3, 5])],
        ];
    }
}
