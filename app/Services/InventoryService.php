<?php
namespace App\Services;
use App\Models\StockBalance;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class InventoryService {
 public function add(int $storeId, int $batchId, int $qty, string $type, string $refType, int $refId): void {
  if (DB::transactionLevel() < 1 || $qty < 1) throw new \LogicException('Valid quantity and active transaction required');
  // In this demo the purchase is the only creator of a stock balance row.
  DB::table('stock_balances')->insertOrIgnore(['store_id'=>$storeId,'medicine_batch_id'=>$batchId,'quantity'=>0,'created_at'=>now(),'updated_at'=>now()]);
  $balance=StockBalance::where('store_id',$storeId)->where('medicine_batch_id',$batchId)->lockForUpdate()->firstOrFail();
  $balance->increment('quantity',$qty);
  StockMovement::create(['store_id'=>$storeId,'medicine_batch_id'=>$batchId,'type'=>$type,'quantity_change'=>$qty,'reference_type'=>$refType,'reference_id'=>$refId]);
 }
 public function deduct(int $storeId, int $batchId, int $qty, string $refType, int $refId): void {
  if (DB::transactionLevel() < 1 || $qty < 1) throw new \LogicException('Valid quantity and active transaction required');
  $balance=StockBalance::where('store_id',$storeId)->where('medicine_batch_id',$batchId)->lockForUpdate()->first();
  if (!$balance || $balance->quantity < $qty) throw ValidationException::withMessages(['quantity'=>'Not enough stock in selected batch.']);
  $balance->decrement('quantity',$qty);
  StockMovement::create(['store_id'=>$storeId,'medicine_batch_id'=>$batchId,'type'=>'sale','quantity_change'=>-$qty,'reference_type'=>$refType,'reference_id'=>$refId]);
 }
}
