<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class TokenNumberService
{
    /**
     * Atomically mint the next token number for today, resetting to 1 each calendar day.
     * Uses a locked counter row per day (not MAX(token_number)+1) so concurrent terminals
     * creating orders at the same instant can't both compute the same next number.
     *
     * @return array{number: int, date: string}
     */
    public function next(): array
    {
        return DB::transaction(function () {
            $today = now()->toDateString();
            $counter = DB::table('daily_token_counters')->where('counter_date', $today)->lockForUpdate()->first();

            if (!$counter) {
                try {
                    DB::table('daily_token_counters')->insert([
                        'counter_date' => $today,
                        'last_number' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    // Another concurrent request created today's row first — fall through and re-select it.
                }
                $counter = DB::table('daily_token_counters')->where('counter_date', $today)->lockForUpdate()->first();
            }

            $next = $counter->last_number + 1;
            DB::table('daily_token_counters')->where('id', $counter->id)->update([
                'last_number' => $next,
                'updated_at' => now(),
            ]);

            return ['number' => $next, 'date' => $today];
        });
    }
}
