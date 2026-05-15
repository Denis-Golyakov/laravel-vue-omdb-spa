<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovieResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->getFieldValue('Title'),
            'year' => $this->getFieldValue('Year'),
            'rated' => $this->getFieldValue('Rated'),
            'released' => $this->getFieldValue('Released'),
            'runtime' => $this->getFieldValue('Runtime'),
            'genre' => $this->getFieldValue('Genre'),
            'director' => $this->getFieldValue('Director'),
            'writer' => $this->getFieldValue('Writer'),
            'actors' => $this->getFieldValue('Actors'),
            'plot' => $this->getFieldValue('Plot'),
            'language' => $this->getFieldValue('Language'),
            'country' => $this->getFieldValue('Country'),
            'awards' => $this->getFieldValue('Awards'),
            'poster' => $this->getFieldValue('Poster'),
            'ratings' => MovieRatingResource::collection($this->resource["Ratings"] ?? []),
            'metascore' => $this->getFieldValue('Metascore'),
            'imdb_rating' => $this->getFieldValue('imdbRating'),
            'imdb_votes' => $this->getFieldValue('imdbVotes'),
            'imdb_id' => $this->getFieldValue('imdbID'),
            'type' => $this->getFieldValue('Type'),
            'dvd' => $this->getFieldValue('DVD'),
            'box_office' => $this->getFieldValue('BoxOffice'),
            'production' => $this->getFieldValue('Production'),
            'website' => $this->getFieldValue('Website'),
        ];
    }

    private function getFieldValue(string $field): ?string
    {
        $value = $this->resource[$field] ?? null;

        return $value === 'N/A' ? null : $value;
    }
}
