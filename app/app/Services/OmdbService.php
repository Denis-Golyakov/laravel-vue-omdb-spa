<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

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

    private function getCacheKey(string $type, string $value): string
    {
        return \sprintf("omdb.%s.%s", $type, md5($value));
    }

    /** @throws \RuntimeException When OMDB returns non-2xx */
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

    /** @return array{Response: string, Title?: string, Year?: string, imdbID?: string, ...} */
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

    /** @return array{Response: string, Search?: list<array{imdbID: string, Title: string, Year: string, Poster: string}>, totalResults?: string, ...} */
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
