<?php

use App\Models\Task;

test('calculateProgress averages loaded children without touching the database', function () {
    // Relasi children di-set manual (in-memory). Jika implementasi memanggil
    // query builder (->children()->avg()), test ini error karena tak ada koneksi DB.
    $parent = new Task(['progress' => 99]);
    $parent->setRelation('children', collect([
        new Task(['progress' => 40]),
        new Task(['progress' => 60]),
    ]));

    expect($parent->calculateProgress())->toBe(50.0);
});

test('calculateProgress falls back to own progress when there are no children', function () {
    $parent = new Task(['progress' => 33]);
    $parent->setRelation('children', collect());

    expect($parent->calculateProgress())->toBe(33.0);
});
