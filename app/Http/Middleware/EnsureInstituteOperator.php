<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class EnsureInstituteOperator {
 public function handle(Request $request, Closure $next): Response {
  if(!$request->user() || !$request->user()->isOperator()) return redirect()->route('institutes.login');
  return $next($request);
 }
}