<?php
namespace App\Services;
use App\Models\StockBalance;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class StockTransferService {
 public function post(int $companyId, int $fromStoreId, int $toStoreId, int $batchId, int $quantity, int $userId, ?string $note=null):int {
  if($fromStoreId===$toStoreId || $quantity<1) throw ValidationException::withMessages(['transfer'=>'Select different stores and a positive quantity.']);
  return DB::transaction(function()use($companyId,$fromStoreId,$toStoreId,$batchId,$quantity,$userId,$note){
   $stores=DB::table('stores')->whereIn('id',[$fromStoreId,$toStoreId])->where('company_id',$companyId)->where('is_active',1)->count();
   if($stores!==2) throw ValidationException::withMessages(['transfer'=>'Both stores must be active and belong to this company.']);
   if(!DB::table('medicine_batches')->where('id',$batchId)->exists()) throw ValidationException::withMessages(['batch'=>'Batch does not exist.']);
   // Ensure destination row exists before acquiring locks. Always lock in store-id order.
   DB::table('stock_balances')->insertOrIgnore(['store_id'=>$toStoreId,'medicine_batch_id'=>$batchId,'quantity'=>0,'created_at'=>now(),'updated_at'=>now()]);
   $rows=StockBalance::whereIn('store_id',[$fromStoreId,$toStoreId])->where('medicine_batch_id',$batchId)->orderBy('store_id')->lockForUpdate()->get()->keyBy('store_id');
   $source=$rows->get($fromStoreId);$destination=$rows->get($toStoreId);
   if(!$source || $source->quantity<$quantity) throw ValidationException::withMessages(['quantity'=>'Insufficient source stock.']);
   if(!$destination) throw new \LogicException('Destination balance could not be initialized.');
   $ref='TR-'.strtoupper(bin2hex(random_bytes(8)));
   $id=DB::table('stock_transfers')->insertGetId(['company_id'=>$companyId,'from_store_id'=>$fromStoreId,'to_store_id'=>$toStoreId,'created_by'=>$userId,'status'=>'posted','reference_no'=>$ref,'note'=>$note,'created_at'=>now(),'updated_at'=>now()]);
   $itemId=DB::table('stock_transfer_items')->insertGetId(['stock_transfer_id'=>$id,'medicine_batch_id'=>$batchId,'quantity'=>$quantity,'created_at'=>now(),'updated_at'=>now()]);
   $source->decrement('quantity',$quantity);$destination->increment('quantity',$quantity);
   StockMovement::create(['store_id'=>$fromStoreId,'medicine_batch_id'=>$batchId,'type'=>'transfer_out','quantity_change'=>-$quantity,'reference_type'=>'stock_transfer_items','reference_id'=>$itemId]);
   StockMovement::create(['store_id'=>$toStoreId,'medicine_batch_id'=>$batchId,'type'=>'transfer_in','quantity_change'=>$quantity,'reference_type'=>'stock_transfer_items','reference_id'=>$itemId]);
   return $id;
  },3);
 }
}