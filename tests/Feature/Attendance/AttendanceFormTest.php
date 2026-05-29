<?php

declare(strict_types=1);

use Kurt\Modules\Events\Attendance\Models\AttendanceForm;

it('validate flags missing required fields', function () {
    $form = AttendanceForm::factory()->create([
        'schema' => [
            ['key' => 'dietary', 'label' => 'Dietary', 'type' => 'text', 'required' => true],
            ['key' => 'tshirt', 'label' => 'T-Shirt', 'type' => 'text', 'required' => false],
        ],
    ]);

    $errors = $form->validate(['tshirt' => 'L']);

    expect($errors)->toHaveKey('dietary');
    expect($errors)->not->toHaveKey('tshirt');
});

it('validate returns empty on valid payload', function () {
    $form = AttendanceForm::factory()->create([
        'schema' => [
            ['key' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
        ],
    ]);

    $errors = $form->validate(['name' => 'Alice']);

    expect($errors)->toBe([]);
});
