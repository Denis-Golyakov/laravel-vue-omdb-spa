<?php

use App\Models\Search;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    config()->set('services.omdb.key', 'test-key');
});

afterEach(fn () => Carbon::setTestNow());

describe('GET /api/movies/search', function () {
    it('returns 200 with mapped search results on success', function () {
        Http::fake([
            '*' => Http::response([
                'Response' => 'True',
                'Search' => [
                    ['imdbID' => 'tt0145487', 'Title' => 'Spider-Man', 'Year' => '2002', 'Poster' => 'http://example.com/p.jpg'],
                ],
                'totalResults' => '1',
            ]),
        ]);

        $response = $this->getJson('/api/movies/search?query=spider');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    [
                        'imdb_id' => 'tt0145487',
                        'title' => 'Spider-Man',
                        'year' => '2002',
                        'poster' => 'http://example.com/p.jpg',
                    ],
                ],
            ]);
    });

    it('normalizes OMDB "N/A" poster to null', function () {
        Http::fake([
            '*' => Http::response([
                'Response' => 'True',
                'Search' => [
                    ['imdbID' => 'tt1', 'Title' => 'X', 'Year' => '2020', 'Poster' => 'N/A'],
                ],
            ]),
        ]);

        $response = $this->getJson('/api/movies/search?query=x');

        $response->assertJsonPath('data.0.poster', null);
    });

    it('returns 200 with empty data when OMDB has no matches', function () {
        Http::fake([
            '*' => Http::response([
                'Response' => 'False',
                'Error' => 'Movie not found!',
            ]),
        ]);

        $response = $this->getJson('/api/movies/search?query=asdfghjkl');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [],
            ]);
    });

    it('returns 503 when the OMDB request fails', function () {
        Http::fake([
            '*' => Http::response(null, 500),
        ]);

        $response = $this->getJson('/api/movies/search?query=anything');

        $response->assertStatus(503)
            ->assertJsonPath('status', 'error');
    });

    it('requires the query parameter', function () {
        $response = $this->getJson('/api/movies/search');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['query']);
    });

    it('rejects queries longer than 100 characters', function () {
        $tooLong = str_repeat('a', 101);

        $response = $this->getJson("/api/movies/search?query={$tooLong}");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['query']);
    });

    it('records the search query to history on a successful search', function () {
        Http::fake([
            '*' => Http::response([
                'Response' => 'True',
                'Search' => [['imdbID' => 'tt1', 'Title' => 'X', 'Year' => '2020', 'Poster' => 'N/A']],
            ]),
        ]);

        $this->getJson('/api/movies/search?query=inception');

        expect(Search::count())->toBe(1)
            ->and(Search::first()->query)->toBe('inception');
    });

    it('does not record queries that returned no matches', function () {
        Http::fake([
            '*' => Http::response([
                'Response' => 'False',
                'Error' => 'Movie not found!',
            ]),
        ]);

        $this->getJson('/api/movies/search?query=asdfghjkl');

        expect(Search::count())->toBe(0);
    });
});

describe('GET /api/movies/{imdbId}', function () {
    it('returns 200 with the mapped movie payload on success', function () {
        Http::fake([
            '*' => Http::response([
                'Response' => 'True',
                'imdbID' => 'tt0145487',
                'Title' => 'Spider-Man',
                'Year' => '2002',
                'Plot' => 'Long plot...',
                'Poster' => 'http://x.com/p.jpg',
                'Ratings' => [],
            ]),
        ]);

        $response = $this->getJson('/api/movies/tt0145487');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'title' => 'Spider-Man',
                    'year' => '2002',
                ],
            ]);
    });

    it('returns 404 when OMDB cannot find the movie', function () {
        Http::fake([
            '*' => Http::response([
                'Response' => 'False',
                'Error' => 'Movie not found!',
            ]),
        ]);

        $response = $this->getJson('/api/movies/tt9999999');

        $response->assertStatus(404)
            ->assertJsonPath('status', 'error');
    });

    it('rejects malformed imdb id at the route level', function () {
        // Route regex /tt\d+/ rejects this before the controller
        $response = $this->getJson('/api/movies/not-an-id');

        $response->assertStatus(404);
    });
});
