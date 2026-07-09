<?php

use App\Models\Task;
use App\Services\DynamicQuery\QueryException;
use App\Services\DynamicQuery\QueryRegistry;
use Tests\TestCase;

uses(TestCase::class);

it('returns a known model definition', function () {
    $registry = app(QueryRegistry::class);

    expect($registry->has('task'))->toBeTrue();

    $task = $registry->get('task');

    expect($task['table'])->toBe('tasks')
        ->and($task['model'])->toBe(Task::class)
        ->and($task['scope'])->toBe('project')
        ->and($task['columns'])->toContain('title')
        ->and($task['relations'])->toHaveKey('status');
});

it('throws an actionable error for an unknown model', function () {
    $registry = app(QueryRegistry::class);

    expect(fn () => $registry->get('nope'))
        ->toThrow(QueryException::class, "Unknown model 'nope'");
});

it('never exposes sensitive user columns', function () {
    $registry = app(QueryRegistry::class);

    $columns = $registry->get('user')['columns'];

    expect($columns)->not->toContain('password')
        ->and($columns)->not->toContain('remember_token')
        ->and($columns)->toContain('email');
});

it('exposes defaults with a hard limit cap', function () {
    $defaults = app(QueryRegistry::class)->defaults();

    expect($defaults['limit'])->toBe(50)
        ->and($defaults['max_limit'])->toBe(200)
        ->and($defaults['allowed_operators'])->toContain('like')
        ->and($defaults['super_admin_roles'])->toContain('super-admin-admin');
});
