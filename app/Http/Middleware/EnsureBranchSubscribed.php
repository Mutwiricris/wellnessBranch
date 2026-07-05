<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBranchSubscribed
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Filament::getTenant();

        if ($tenant) {
            $owner = $tenant->owner;
            
            // If the route name is subscription-hold, let it pass
            if ($request->routeIs('filament.admin.tenant.pages.subscription-hold') || 
                str_contains($request->route()?->getName() ?? '', 'subscription-hold')) {
                return $next($request);
            }

            if (!$owner || !$owner->hasActiveSubscription()) {
                return redirect()->route('filament.admin.tenant.pages.subscription-hold', ['tenant' => $tenant]);
            }
        }

        return $next($request);
    }
}
