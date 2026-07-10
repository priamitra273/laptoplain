<?php

use App\Facades\Sqids;
use App\Services\DynamicQuery\DynamicQueryService;
use App\Services\DynamicQuery\QueryException;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->service = app(DynamicQueryService::class);
});

it('defaults select to the allowlist and applies limit/mode defaults', function () {
    $n = $this->service->validate(['model' => 'task']);

    expect($n['mode'])->toBe('relation')
        ->and($n['table'])->toBe('tasks')
        ->and($n['limit'])->toBe(50)
        ->and($n['offset'])->toBe(0)
        ->and($n['select'])->toContain('title')      // allowlist-derived
        ->and($n['tables'])->toBe(['tasks']);
});

it('rejects an unknown column', function () {
    expect(fn () => $this->service->validate([
        'model' => 'task',
        'select' => ['no_such_column'],
    ]))->toThrow(QueryException::class, 'no_such_column');
});

it('rejects an operator outside the allowlist', function () {
    expect(fn () => $this->service->validate([
        'model' => 'task',
        'filters' => [['column' => 'progress', 'operator' => 'BETWEEN', 'value' => 1]],
    ]))->toThrow(QueryException::class, 'operator');
});

it('rejects combining with and aggregates', function () {
    expect(fn () => $this->service->validate([
        'model' => 'task',
        'with' => ['status'],
        'aggregates' => [['function' => 'count', 'column' => 'tasks.id', 'alias' => 'total']],
    ]))->toThrow(QueryException::class, 'cannot be combined');
});

it('treats an aggregate with no select and no group_by as a global aggregate', function () {
    $n = $this->service->validate([
        'model' => 'task',
        'aggregates' => [['function' => 'count', 'column' => '*', 'alias' => 'total']],
    ]);

    expect($n['mode'])->toBe('join')
        ->and($n['select'])->toBe([])
        ->and($n['group_by'])->toBe([])
        ->and($n['aggregates'])->toBe([['function' => 'count', 'column' => '*', 'alias' => 'total']]);
});

it('defaults an aggregate select to the grouped columns when group_by is given', function () {
    $n = $this->service->validate([
        'model' => 'task',
        'group_by' => ['tasks.status_id'],
        'aggregates' => [['function' => 'count', 'column' => '*', 'alias' => 'total']],
    ]);

    expect($n['select'])->toBe(['tasks.status_id'])
        ->and($n['group_by'])->toBe(['tasks.status_id']);
});

it('caps the limit at the hard maximum', function () {
    expect(fn () => $this->service->validate([
        'model' => 'task',
        'limit' => 5000,
    ]))->toThrow(QueryException::class, 'limit');
});

it('rejects a join to a non-joinable model', function () {
    expect(fn () => $this->service->validate([
        'model' => 'task',
        'joins' => [[
            'model' => 'ms_project_role',
            'on' => [['left' => 'tasks.id', 'right' => 'ms_project_roles.id']],
        ]],
    ]))->toThrow(QueryException::class, 'join');
});

it('decodes sqid id filter values to integers', function () {
    $encoded = Sqids::encode(123);

    $n = $this->service->validate([
        'model' => 'task',
        'filters' => [['column' => 'project_id', 'operator' => '=', 'value' => $encoded]],
    ]);

    expect($n['filters'][0]['value'])->toBe(123)
        ->and($n['filters'][0]['is_id'])->toBeTrue();
});

it('rejects an aggregate over a non-allowlisted column', function () {
    expect(fn () => $this->service->validate([
        'model' => 'task',
        'joins' => [['model' => 'user', 'on' => [['left' => 'tasks.owned_id', 'right' => 'users.id']]]],
        'group_by' => ['users.name'],
        'aggregates' => [['function' => 'max', 'column' => 'users.password', 'alias' => 'p']],
    ]))->toThrow(QueryException::class, 'password');
});

it('rejects a join on-condition referencing a non-allowlisted column', function () {
    expect(fn () => $this->service->validate([
        'model' => 'task',
        'joins' => [['model' => 'user', 'on' => [['left' => 'tasks.owned_id', 'right' => 'users.password']]]],
    ]))->toThrow(QueryException::class, 'password');
});
