<?php

use App\Models\Search;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

afterEach(fn () => Carbon::setTestNow());

it('scopes queries to a specific session via forSession', function () {
    Search::create(['session_id' => 'session-a', 'query' => 'inception']);
    Search::create(['session_id' => 'session-b', 'query' => 'matrix']);

    $result = Search::forSession('session-a')->get();

    expect($result)->toHaveCount(1)
        ->and($result->first()->query)->toBe('inception');
});

it('orders results by updated_at descending via recent scope', function () {
    Carbon::setTestNow('2024-01-01 12:00:00');
    Search::create(['session_id' => 'sess', 'query' => 'first']);

    Carbon::setTestNow('2024-01-01 12:01:00');
    Search::create(['session_id' => 'sess', 'query' => 'second']);

    Carbon::setTestNow('2024-01-01 12:02:00');
    Search::create(['session_id' => 'sess', 'query' => 'third']);

    $result = Search::forSession('sess')->recent()->get();

    expect($result->pluck('query')->toArray())
        ->toBe(['third', 'second', 'first']);
});

it('respects the default limit of 5 in recent scope', function () {
    foreach (range(1, 10) as $i) {
        Carbon::setTestNow(Carbon::parse('2024-01-01 12:00:00')->addMinutes($i));
        Search::create(['session_id' => 'sess', 'query' => "query-{$i}"]);
    }

    $result = Search::forSession('sess')->recent()->get();

    expect($result)->toHaveCount(5);
});

it('accepts a custom limit in recent scope', function () {
    foreach (range(1, 10) as $i) {
        Search::create(['session_id' => 'sess', 'query' => "query-{$i}"]);
    }

    $result = Search::forSession('sess')->recent(3)->get();

    expect($result)->toHaveCount(3);
});

it('dedupes by (session_id, query) when using updateOrCreate with touch', function () {
    Carbon::setTestNow('2024-01-01 12:00:00');
    Search::updateOrCreate(['session_id' => 'sess', 'query' => 'inception'])->touch();
    $initialUpdatedAt = Search::first()->updated_at;

    Carbon::setTestNow('2024-01-01 12:00:05');
    Search::updateOrCreate(['session_id' => 'sess', 'query' => 'inception'])->touch();

    expect(Search::count())->toBe(1)
        ->and(Search::first()->updated_at->gt($initialUpdatedAt))->toBeTrue();
});
