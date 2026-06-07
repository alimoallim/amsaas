<?php
namespace App\Http\Middleware;
use App\Services\MultiTenancy\TenancyManager; // 1. Import the class
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // 2. Import the Auth Facade

class SetTenantContext
{
    public function handle(Request $request, Closure $next)
    {
        // Populate the TenancyManager if user is authenticated
        if (Auth::check() && Auth::user() !== null) {
            
            app(TenancyManager::class)->setCompanyId(Auth::user()->company_id);
            
        }
        

        return $next($request);
    }
}