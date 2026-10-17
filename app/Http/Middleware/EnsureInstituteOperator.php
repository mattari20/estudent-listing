<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class EnsureInstituteOperator {
 public function handle(Request $request, Closure $next): Response {
  if(!$request->user() || !$request->user()->isOperator()) return redirect()->route('institutes.login');
  if($request->user()->must_change_password && !$request->routeIs('institutes.operations.account.password.edit', 'institutes.operations.account.password.update', 'institutes.logout')) return redirect()->route('institutes.operations.account.password.edit')->with('status', 'You must change your temporary password before continuing.');
  return $next($request);
 }
}