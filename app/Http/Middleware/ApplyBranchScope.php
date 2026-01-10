<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyBranchScope
{
    /**
     * Handle an incoming request.
     *
     * Apply branch scoping to all queries when a tenant (branch) is active.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();

        if ($tenant) {
            // Store current branch ID in application container
            // This allows repositories and services to access it
            app()->instance('current_branch_id', $tenant->id);

            // Store in config for easier access
            config(['app.current_branch_id' => $tenant->id]);
        }

        return $next($request);
    }
}
