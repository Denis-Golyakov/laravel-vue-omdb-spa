<?php

use App\Http\Controllers\Api\SearchHistoryController;
use App\Models\Search;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

afterEach(fn() => Carbon::setTestNow());

it('returns an empty data array when no history exists', function () {
    $response = $this->getJson('/api/searches');

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data' => [],
        ]);
});

it('does not return history from other sessions', function () {
    // Seed rows for a different session - the test request will auto-generate
    // its own (different) session id, so the controller should see no rows
    Search::create(['session_id' => 'someone-elses-session', 'query' => 'should-not-appear']);

    $response = $this->getJson('/api/searches');

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data' => [],
        ]);
});

/**
 * The two tests below invoke the controller directly rather than going through
 * an HTTP request. They need the controller's session id to match the
 * seeded rows session_id, and Laravel's test client doesn't reliably allow
 * pinning the session id of an incoming request via cookies (verified
 * empirically - the cookie is dropped or overridden somewhere in the
 * pipeline). Direct invocation gives us full control over the session
 * attached to the request and keeps the test focused on controller logic.
 */

it('returns history for the current session in recent-first order', function () {
    $sessionId = str_repeat('a', 40); // valid 40-char alnum session id

    Carbon::setTestNow('2024-01-01 12:00:00');
    Search::create(['session_id' => $sessionId, 'query' => 'oldest']);

    Carbon::setTestNow('2024-01-01 12:01:00');
    Search::create(['session_id' => $sessionId, 'query' => 'middle']);

    Carbon::setTestNow('2024-01-01 12:02:00');
    Search::create(['session_id' => $sessionId, 'query' => 'newest']);

    $request = Request::create('/api/searches', 'GET');
    $session = app('session.store');
    $session->setId($sessionId);
    $request->setLaravelSession($session);

    $response = app(SearchHistoryController::class)->index($request);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getData(true))->toBe([
                'status' => 'success',
                'data' => ['newest', 'middle', 'oldest'],
            ]);
});

it('limits returned history to 5 entries even when more exist', function () {
    $sessionId = str_repeat('a', 40);

    foreach (range(1, 10) as $i) {
        Carbon::setTestNow(Carbon::parse('2024-01-01 12:00:00')->addMinutes($i));
        Search::create(['session_id' => $sessionId, 'query' => "query-{$i}"]);
    }

    $request = Request::create('/api/searches', 'GET');
    $session = app('session.store');
    $session->setId($sessionId);
    $request->setLaravelSession($session);

    $response = app(SearchHistoryController::class)->index($request);

    expect($response->getData(true)['data'])->toHaveCount(5);
});
