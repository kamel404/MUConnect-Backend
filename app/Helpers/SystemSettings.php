<?php

use Illuminate\Support\Facades\DB;

if (!function_exists('getVotingStatus')) {
    function getVotingStatus(): string
    {
        return DB::table('settings')->where('key', 'voting_status')->value('value') ?? 'closed';
    }
}
