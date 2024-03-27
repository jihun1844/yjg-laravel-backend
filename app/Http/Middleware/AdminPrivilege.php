<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminPrivilege
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$privileges): Response
    {
        $adminId = auth()->id();
        try {
            $admin = Admin::findOrFail($adminId);
        } catch (ModelNotFoundException $modelException) {
            return response()->json(['error' => '해당하는 관리자가 없습니다.'], 404);
        }

        // TODO: 권한 확인하는 것 구현해야함

        return $next($request);
    }
}