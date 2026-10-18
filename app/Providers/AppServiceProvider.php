<?php
namespace App\Providers;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
class AppServiceProvider extends ServiceProvider {
 public function register(): void {}
 public function boot(): void {
  Gate::define('manage-users',fn(User $u)=>$u->is_active && $u->role==='admin');
  Gate::define('manage-pharmacy',fn(User $u)=>$u->is_active && in_array($u->role,['admin','manager'],true));
  Gate::define('bill-pharmacy',fn(User $u)=>$u->is_active && in_array($u->role,['admin','manager','pharmacist','cashier'],true));
 }
}
