<?php

use App\Models\User;

it('mass assigns the Backpack enabled field to the persisted active attribute', function () {
    $user = new User(['isactive' => false]);

    $user->fill(['is_enabled' => '1']);

    expect($user->isactive)->toBe('1')
        ->and($user->isDirty('isactive'))->toBeTrue();
});
