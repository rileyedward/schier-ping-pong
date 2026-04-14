<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMatchupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $matchup = $this->route('matchup');

        if ($matchup && $matchup->isDoubles()) {
            $seasonId = $matchup->season_id;

            return [
                'team_one_id' => [
                    'required', 'integer', 'different:team_two_id',
                    Rule::exists('teams', 'id')->where('season_id', $seasonId),
                ],
                'team_two_id' => [
                    'required', 'integer',
                    Rule::exists('teams', 'id')->where('season_id', $seasonId),
                ],
                'scheduled_for' => ['required', 'date'],
                'best_of' => ['required', Rule::in([3, 5])],
            ];
        }

        return [
            'player_one_id' => ['required', 'integer', 'exists:players,id', 'different:player_two_id'],
            'player_two_id' => ['required', 'integer', 'exists:players,id'],
            'scheduled_for' => ['required', 'date'],
            'best_of' => ['required', Rule::in([3, 5])],
        ];
    }
}
