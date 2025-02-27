<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
{
    $sharedProps = array_merge(parent::share($request), [
        // Application name
        'appName' => config('app.name'),

        // Current authenticated user
        'auth.user' => fn () => $request->user(),

        // Current route name
        'currentRoute' => $request->route() ? $request->route()->getName() : null,

        // CSRF token for Inertia requests
        'csrf_token' => csrf_token(),
    ]);

    // Debugging: Log the shared props
    Log::info('Shared props:', $sharedProps);

    return $sharedProps;
}

}
