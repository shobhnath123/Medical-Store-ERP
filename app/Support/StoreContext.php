<?php
namespace App\Support;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
final class StoreContext {
 public static function id(): int {
  $id=(int)session('pharmacy_store_id',0);
  if (!$id || !Auth::check()) throw new RuntimeException('Authorized store context missing.');
  abort_unless(DB::table('store_user as su')
    ->join('stores as s','s.id','=','su.store_id')
    ->join('companies as c','c.id','=','s.company_id')
    ->where('su.user_id',Auth::id())->where('su.store_id',$id)
    ->where('s.is_active',1)->where('c.is_active',1)->exists(),403);
  return $id;
 }
 public static function companyId(): int {
  $company=DB::table('stores')->where('id',self::id())->value('company_id');
  abort_unless($company,403); return (int)$company;
 }
 public static function assertOwns(string $table, int $id): void {
  abort_unless(DB::table($table)->where('id',$id)->where('company_id',self::companyId())->exists(),403);
 }
}
