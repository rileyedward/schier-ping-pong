<?php

namespace App\Services\Rating;

class UsattRating
{
    private const TABLE = [
        [12, 8, 8],
        [37, 7, 10],
        [62, 6, 13],
        [87, 5, 16],
        [112, 4, 20],
        [137, 3, 25],
        [162, 2, 30],
        [187, 2, 35],
        [212, 1, 40],
        [237, 1, 45],
    ];

    private const UPSET_FLOOR = [0, 50];

    public static function delta(int $winnerRating, int $loserRating): int
    {
        $diff = abs($winnerRating - $loserRating);
        $upset = $winnerRating < $loserRating;

        foreach (self::TABLE as [$max, $expected, $upsetPoints]) {
            if ($diff <= $max) {
                return $upset ? $upsetPoints : $expected;
            }
        }

        return $upset ? self::UPSET_FLOOR[1] : self::UPSET_FLOOR[0];
    }
}
