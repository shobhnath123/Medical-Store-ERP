<?php
namespace App\Services;
use App\Models\{Sale,SaleItem,Purchase,PurchaseItem,StockBalance,StockMovement};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class ReturnService {
 // Base unit quantities. Returned customer medicine always held in quarantine,
 // never automatically added to saleable stock.
 public function salesReturn(array $data,int $userId): int {
  return DB::transaction(function() use($data,$userId){
   $sale=Sale::whereKey($data['sale_id'])->lockForUpdate()->firstOrFail();
   if($sale->status!=='posted') throw ValidationException::withMessages(['sale_id'=>'Only posted sales can be returned.']);
   $item=SaleItem::where('sale_id',$sale->id)->whereKey($data['sale_item_id'])->lockForUpdate()->firstOrFail();
   $previous=(int)DB::table('sales_return_items')->join('sales_returns','sales_returns.id','=','sales_return_items.sales_return_id')->where('sales_return_items.sale_item_id',$item->id)->where('sales_returns.status','posted')->sum('sales_return_items.quantity');
   $qty=(int)$data['quantity'];
   if($qty<1 || $qty > $item->quantity-$previous) throw ValidationException::withMessages(['quantity'=>'Quantity exceeds unreturned sale quantity.']);
   // proportional historical net price; rounding correction required on final partial return.
   $cents=(int)round((float)$item->line_total*100);
   $alreadyRefunded=(int)round((float)DB::table('sales_return_items')->join('sales_returns','sales_returns.id','=','sales_return_items.sales_return_id')->where('sales_return_items.sale_item_id',$item->id)->where('sales_returns.status','posted')->sum('sales_return_items.refund_amount')*100);
   $refund=($qty===$item->quantity-$previous)?$cents-$alreadyRefunded:(int)round($cents*$qty/$item->quantity);
   $id=DB::table('sales_returns')->insertGetId(['sale_id'=>$sale->id,'store_id'=>$sale->store_id,'created_by'=>$userId,'reason'=>$data['reason'],'refund_due'=>$refund/100,'refund_status'=>'pending','status'=>'posted','created_at'=>now(),'updated_at'=>now()]);
   DB::table('sales_return_items')->insert(['sales_return_id'=>$id,'sale_item_id'=>$item->id,'quantity'=>$qty,'refund_amount'=>$refund/100,'disposition'=>'quarantine','created_at'=>now(),'updated_at'=>now()]);
   // Stock is not credited. Inspection and explicit release process needed.
   return $id;
  },3);
 }
 public function purchaseReturn(array $data,int $userId): int {
  return DB::transaction(function() use($data,$userId){
   $purchase=Purchase::whereKey($data['purchase_id'])->lockForUpdate()->firstOrFail();
   if($purchase->status!=='posted') throw ValidationException::withMessages(['purchase_id'=>'Only posted purchases can be returned.']);
   $item=PurchaseItem::where('purchase_id',$purchase->id)->whereKey($data['purchase_item_id'])->lockForUpdate()->firstOrFail();
   $prior=(int)DB::table('purchase_return_items')->where('purchase_item_id',$item->id)->sum('quantity');
   $qty=(int)$data['quantity'];
   if($qty<1 || $qty>$item->quantity-$prior) throw ValidationException::withMessages(['quantity'=>'Return exceeds quantity received on purchase line.']);
   $balance=StockBalance::where('store_id',$purchase->store_id)->where('medicine_batch_id',$item->medicine_batch_id)->lockForUpdate()->first();
   if(!$balance || $balance->quantity<$qty) throw ValidationException::withMessages(['quantity'=>'Insufficient sellable batch stock for supplier return.']);
   $cents=(int)round((float)$item->line_total*100);
   $already=(int)round((float)DB::table('purchase_return_items')->where('purchase_item_id',$item->id)->sum('credit_amount')*100);
   $credit=$qty===$item->quantity-$prior?$cents-$already:(int)round($qty*$cents/$item->quantity);
   $id=DB::table('purchase_returns')->insertGetId(['purchase_id'=>$purchase->id,'store_id'=>$purchase->store_id,'created_by'=>$userId,'reason'=>$data['reason'],'supplier_credit_due'=>$credit/100,'settlement_status'=>'pending','created_at'=>now(),'updated_at'=>now()]);
   $lineId=DB::table('purchase_return_items')->insertGetId(['purchase_return_id'=>$id,'purchase_item_id'=>$item->id,'quantity'=>$qty,'credit_amount'=>$credit/100,'created_at'=>now(),'updated_at'=>now()]);
   $balance->decrement('quantity',$qty);
   StockMovement::create(['store_id'=>$purchase->store_id,'medicine_batch_id'=>$item->medicine_batch_id,'type'=>'purchase_return','quantity_change'=>-$qty,'reference_type'=>'purchase_return_items','reference_id'=>$lineId]);
   return $id;
  },3);
 }
 public function adjust(array $data,int $userId): int {
  return DB::transaction(function()use($data,$userId){
   $storeId=1; // DEMO only. Replace with authorized store context.
   $qty=(int)$data['quantity_change'];
   if($qty===0 || abs($qty)>1000000) throw ValidationException::withMessages(['quantity_change'=>'Invalid adjustment quantity.']);
   $balance=StockBalance::where('store_id',$storeId)->where('medicine_batch_id',$data['medicine_batch_id'])->lockForUpdate()->firstOrFail();
   if($qty<0 && $balance->quantity<abs($qty)) throw ValidationException::withMessages(['quantity_change'=>'Not enough stock.']);
   $id=DB::table('stock_adjustments')->insertGetId(['store_id'=>$storeId,'medicine_batch_id'=>$data['medicine_batch_id'],'created_by'=>$userId,'quantity_change'=>$qty,'reason'=>$data['reason'],'created_at'=>now(),'updated_at'=>now()]);
   $balance->quantity += $qty; $balance->save();
   StockMovement::create(['store_id'=>$storeId,'medicine_batch_id'=>$data['medicine_batch_id'],'type'=>'adjustment','quantity_change'=>$qty,'reference_type'=>'stock_adjustments','reference_id'=>$id]);
   return $id;
  },3);
 }
}