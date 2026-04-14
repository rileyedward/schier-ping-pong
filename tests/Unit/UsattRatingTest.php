<?php

use App\Services\Rating\UsattRating;

it('returns expected-win points for tight rating gaps', function () {
    expect(UsattRating::delta(1200, 1200))->toBe(8);
    expect(UsattRating::delta(1212, 1200))->toBe(8);
    expect(UsattRating::delta(1213, 1200))->toBe(7);
    expect(UsattRating::delta(1237, 1200))->toBe(7);
    expect(UsattRating::delta(1238, 1200))->toBe(6);
});

it('returns upset points when lower-rated wins', function () {
    expect(UsattRating::delta(1200, 1213))->toBe(10);
    expect(UsattRating::delta(1200, 1238))->toBe(13);
});

it('caps at table floor for huge gaps', function () {
    expect(UsattRating::delta(2000, 1000))->toBe(0);
    expect(UsattRating::delta(1000, 2000))->toBe(50);
});

it('swings harder on upset than expected win for same gap', function () {
    $gap = 100;
    $expected = UsattRating::delta(1200 + $gap, 1200);
    $upset = UsattRating::delta(1200, 1200 + $gap);
    expect($upset)->toBeGreaterThan($expected);
});
