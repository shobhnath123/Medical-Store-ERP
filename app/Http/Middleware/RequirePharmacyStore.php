<?php
namespace App\Http\Middleware;
use App\Support\StoreContext;
use Closure;
use Illuminate\Http\Request;
class RequirePharmacyStore {
 public function handle(Request $request, Closure $next) {
  if(!$request->session()->has('pharmacy_store_id')) return redirect()->route('store.select');
  StoreContext::id();
  return $next($request);
 }
}
