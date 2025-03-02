<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UnrestrictUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:unrestrict-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unrestrict users whose restriction period has ended';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::where('is_restricted', true)
            ->where('restriction_end', '<', Carbon::now())
            ->get();

        foreach ($users as $user) {
            $user->is_restricted = false;
            $user->restriction_start = null;
            $user->restriction_end = null;
            $user->save();

            $this->info("User ID {$user->id} has been unrestricted.");
        }

        $this->info("Unrestricted {$users->count()} users.");
    }
}
