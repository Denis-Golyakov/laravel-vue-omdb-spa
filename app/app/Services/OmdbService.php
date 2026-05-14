<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * OmdbService class.
 *
 * This class provides methods for interacting with the OMDB API.
 */
class OmdbService
{
    private readonly string $apiKey;
    private readonly string $apiRoute;
    private const int CACHE_TTL = 3600;
    private const int HTTP_TIMEOUT = 10;

    public function __construct()
    {
        $this->apiKey = config('services.omdb.key');
        $this->apiRoute = config('services.omdb.route');
    }

    /**
     * Generate a cache key for the given type and value.
     *
     * @param string $type The type of the cache key.
     * @param string $value The value to generate the cache key from.
     * @return string The generated cache key.
     */
    private function getCacheKey(string $type, string $value): string
    {
        return \sprintf("omdb.%s.%s", $type, md5($value));
    }

    /**
     * Fetch data from the OMDB API and cache it.
     *
     * @param string $cacheKey The cache key.
     * @param array $parameters The request parameters.
     * @return array The fetched data.
     */
    private function fetch(string $cacheKey, array $parameters): array
    {
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($parameters) {
            $response = Http::timeout(self::HTTP_TIMEOUT)
                ->get($this->apiRoute, [
                    'apikey' => $this->apiKey,
                    ...$parameters
                ]);

            if (!$response->successful()) {
                throw new \RuntimeException("OMDB request failed: HTTP {$response->status()}");
            }

            return $response->json() ?? [];
        });
    }

    /**
     * Find movie details by IMDb ID.
     *
     * @param string $imdbId The IMDb ID of the movie.
     * @return array The movie details.
     */
    public function find(string $imdbId): array
    {
        return $this->fetch(
            $this->getCacheKey("find", $imdbId),
            [
                'i' => $imdbId,
                'plot' => 'full',
            ]
        );
    }

    /**
     * Search for movies by query.
     *
     * @param string $searchQuery The search query.
     * @return array The search results.
     */
    public function search(string $searchQuery): array
    {
        return $this->fetch(
            $this->getCacheKey("search", $searchQuery),
            [
                's' => $searchQuery,
                'type' => 'movie',
            ]
        );
    }
}
