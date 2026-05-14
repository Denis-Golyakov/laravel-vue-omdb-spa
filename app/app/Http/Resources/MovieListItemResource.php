<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovieListItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'imdb_id' => $this->resource['imdbID'],
            'title' => $this->resource['Title'],
            'year' => $this->resource['Year'],
            'poster' => $this->resource['Poster'] !== 'N/A' ? $this->resource['Poster'] : null,
        ];
    }
}
