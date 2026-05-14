<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Search;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchHistoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $queries = Search::forSession($request->session()->getId())
            ->recent()
            ->pluck('query');

        return response()->json([
            'status' => 'success',
            'data' => $queries->toArray()
        ]);
    }
}
