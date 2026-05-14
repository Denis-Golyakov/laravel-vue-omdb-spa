<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['session_id', 'query'])]
class Search extends Model
{
    private const int RECENT_LIMIT = 5;

    public function scopeForSession(Builder $query, string $sessionId): Builder
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeRecent(Builder $query, int $limit = self::RECENT_LIMIT): Builder
    {
        return $query->orderByDesc('updated_at')->limit($limit);
    }
}
