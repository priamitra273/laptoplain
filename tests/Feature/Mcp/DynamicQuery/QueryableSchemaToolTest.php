<?php

use App\Mcp\Servers\ProjectManagementServer;
use App\Mcp\Tools\QueryableSchemaTool;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(['id' => 1]);
});

it('lists queryable models with their columns and relations', function () {
    $response = ProjectManagementServer::actingAs($this->user)
        ->tool(QueryableSchemaTool::class);

    $response->assertOk();
    $response->assertSee('task');
    $response->assertSee('ms_task_statuses');
    $response->assertSee('like');   // allowed operator surfaced
    $response->assertSee('email');  // proves the user model is present with its safe columns

    // Sensitive columns must never be advertised. assertDontSee() inspects the same
    // response content assertSee() does, so this is a real (non-vacuous) negative check.
    $response->assertDontSee('password');
});
