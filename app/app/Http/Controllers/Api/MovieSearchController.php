<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchMoviesRequest;
use App\Http\Resources\MovieListItemResource;
use App\Http\Resources\MovieResource;
use App\Models\Search;
use App\Services\OmdbService;
use Illuminate\Http\JsonResponse;

class MovieSearchController extends Controller
{
    public function __construct(
        private readonly OmdbService $service
    ) {
    }

    public function search(SearchMoviesRequest $request): JsonResponse
    {
        $searchQuery = $request->validated('query');
        try {
            $searchResults = $this->service->search($searchQuery);
        } catch (\Exception $e) {
            report($e);
            return $this->errorResponse('Unable to fetch movies right now.', 503);
        }

        $response = [
            'status' => 'success',
            'data' => []
        ];

        if ($searchResults['Response'] === 'True') {
            $response['data'] = MovieListItemResource::collection($searchResults['Search']);
            // touch() to affect updated_at and not created_at
            Search::updateOrCreate([
                'session_id' => $request->session()->getId(),
                'query' => $searchQuery
            ])->touch();
        }

        return response()->json($response);
    }

    public function show(string $imdbId): JsonResponse
    {
        try {
            $movieResults = $this->service->find($imdbId);
        } catch (\Exception $e) {
            report($e);
            return $this->errorResponse('Unable to obtain movie information right now.', 503);
        }

        $response = [
            'status' => '',
            'data' => []
        ];
        $responseStatusCode = 200;
        if ($movieResults['Response'] === 'True') {
            $response['status'] = 'success';
            $response['data'] = MovieResource::make($movieResults);
        } else {
            $response['status'] = 'error';
            $response['message'] = $movieResults['Error'];
            $responseStatusCode = 404; // Not Found
        }

        return response()->json($response, $responseStatusCode);
    }

    private function errorResponse(string $message, int $status = 500): JsonResponse
    {
        return response()->json(['status' => 'error', 'message' => $message], $status);
    }
}
