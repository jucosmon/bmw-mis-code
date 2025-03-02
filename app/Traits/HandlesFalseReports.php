<?php

namespace App\Traits;

use App\Models\User;
use Carbon\Carbon;

trait HandlesFalseReports
{
    /**
     * @return void
     */
    public function handleFalseReport($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return null;
        }

        $user->false_report_count++;

        if ($user->false_report_count >= 3 && !$user->is_restricted) {
            $user->is_restricted = true;
            $user->restriction_start = Carbon::now();
            $user->restriction_end = Carbon::now()->addDays(15);
        }

        $user->save();
    }
}
