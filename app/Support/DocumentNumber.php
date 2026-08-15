<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DocumentNumber
{
    /**
     * Next number in a "PREFIX00001" series, derived from the highest number
     * already issued instead of from the row count.
     *
     * Counting rows breaks the moment one is deleted: the count falls back to
     * a value that is still in use, and the (company_id, number) unique index
     * rejects the insert with a 1062 duplicate entry. That surfaced as a hard
     * "Server Error" on save — and it never healed by itself, because every
     * retry recomputed the same taken number, so the screen stayed broken for
     * that company (e.g. a customer payment that could no longer be recorded).
     */
    public static function next(Builder $query, string $column, string $prefix, int $pad = 5): string
    {
        $column = preg_replace('/[^A-Za-z0-9_]/', '', $column);

        $highest = (int) $query->clone()
            ->where($column, 'like', $prefix . '%')
            ->max(DB::raw('CAST(SUBSTRING(`' . $column . '`, ' . (strlen($prefix) + 1) . ') AS UNSIGNED)'));

        return $prefix . str_pad($highest + 1, $pad, '0', STR_PAD_LEFT);
    }
}
