<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVendorIsApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user() ?? $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'code' => 'UNAUTHORIZED',
            ], 401);
        }

        $roleValue = $user->role instanceof UserRole ? $user->role->value : $user->role;

        if ($roleValue !== UserRole::Vendor->value) {
            return response()->json([
                'message' => 'Only vendors can perform this action.',
                'code' => 'NOT_VENDOR',
            ], 403);
        }

        if (! $user->vendor || ! $user->vendor->is_approved) {
            return response()->json([
                'message' => 'Your vendor account is pending approval. You cannot manage products until approved.',
                'code' => 'VENDOR_NOT_APPROVED',
            ], 403);
        }

        return $next($request);
    }
}
