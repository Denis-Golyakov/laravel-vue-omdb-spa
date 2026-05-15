<?php

use App\Services\OmdbService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
    config()->set('services.omdb.key', 'test-key');
    config()->set('services.omdb.route', 'https://www.omdbapi.com');
});

it('calls OMDB with movie type filter when searching', function () {
    Http::fake([
        '*' => Http::response([
            'Response' => 'True',
            'Search' => [
                ['imdbID' => 'tt0145487', 'Title' => 'Spider-Man', 'Year' => '2002', 'Poster' => 'http://example.com/p.jpg'],
            ],
            'totalResults' => '1',
        ]),
    ]);

    $service = new OmdbService();
    $result = $service->search('spider');

    expect($result['Response'])->toBe('True')
        ->and($result['Search'])->toHaveCount(1);

    Http::assertSent(
        fn($request) =>
        $request['apikey'] === 'test-key'
        && $request['s'] === 'spider'
        && $request['type'] === 'movie'
    );
});

it('calls OMDB with full plot when finding by imdb id', function () {
    Http::fake([
        '*' => Http::response([
            'Response' => 'True',
            'imdbID' => 'tt0145487',
            'Title' => 'Spider-Man',
            'Plot' => 'Long plot text...',
        ]),
    ]);

    $service = new OmdbService();
    $result = $service->find('tt0145487');

    expect($result['Title'])->toBe('Spider-Man');

    Http::assertSent(
        fn($request) =>
        $request['apikey'] === 'test-key'
        && $request['i'] === 'tt0145487'
        && $request['plot'] === 'full'
    );
});

it('throws RuntimeException on non-2xx response', function () {
    Http::fake([
        '*' => Http::response(null, 500),
    ]);

    $service = new OmdbService();

    expect(fn() => $service->search('anything'))
        ->toThrow(\RuntimeException::class);
});

it('caches results - second call with same params skips HTTP', function () {
    Http::fake([
        '*' => Http::response(['Response' => 'True', 'Search' => []]),
    ]);

    $service = new OmdbService();
    $service->search('spider');
    $service->search('spider');

    Http::assertSentCount(1);
});

it('uses distinct cache keys for search and find with the same value', function () {
    Http::fake([
        '*' => Http::sequence()
            ->push(['Response' => 'True', 'Search' => []])
            ->push(['Response' => 'True', 'Title' => 'X']),
    ]);

    $service = new OmdbService();
    $service->search('tt0145487');
    $service->find('tt0145487');

    // Different cache keys → both calls hit HTTP
    Http::assertSentCount(2);
});

it('does not cache failed responses', function () {
    Http::fake([
        '*' => Http::sequence()
            ->push(null, 500)
            ->push(['Response' => 'True', 'Search' => []], 200),
    ]);

    $service = new OmdbService();

    try {
        $service->search('spider');
    } catch (\RuntimeException) {
        // expected - first call fails
    }

    // Second call should hit HTTP again because the failure wasn't cached
    $result = $service->search('spider');

    expect($result['Response'])->toBe('True');
    Http::assertSentCount(2);
});
