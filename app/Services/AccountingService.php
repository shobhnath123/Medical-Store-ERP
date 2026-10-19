<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class AccountingService {
 /** Monetary amounts in integer paise. Call from an outer DB transaction. */
 public function post(int $storeId,string $type,int $sourceId,string $description,array $lines,?int $userId=null):int {
  if(DB::transactionLevel()===0) throw new \LogicException('Accounting posting requires an open transaction.');
  $dr=0;$cr=0;
  foreach($lines as $l){$dr+=(int)($l['debit']??0);$cr+=(int)($l['credit']??0);}
  if($dr<=0||$dr!==$cr) throw ValidationException::withMessages(['accounting'=>'Journal must have equal nonzero debits and credits.']);
  if(DB::table('journal_entries')->where('store_id',$storeId)->where('reference_type',$type)->where('reference_id',$sourceId)->exists()) throw ValidationException::withMessages(['accounting'=>'This source was already posted to the ledger.']);
  $codes=array_unique(array_column($lines,'code'));
  $accounts=DB::table('accounts')->where('store_id',$storeId)->where('is_active',1)->whereIn('code',$codes)->pluck('id','code');
  if($accounts->count()!==count($codes)) throw ValidationException::withMessages(['accounting'=>'Missing or inactive ledger account.']);
  $id=DB::table('journal_entries')->insertGetId(['store_id'=>$storeId,'entry_date'=>now()->toDateString(),'reference_type'=>$type,'reference_id'=>$sourceId,'description'=>$description,'created_by'=>$userId,'created_at'=>now(),'updated_at'=>now()]);
  foreach($lines as $l) DB::table('journal_lines')->insert(['journal_entry_id'=>$id,'account_id'=>$accounts[$l['code']],'debit'=>round(($l['debit']??0)/100,2),'credit'=>round(($l['credit']??0)/100,2),'created_at'=>now(),'updated_at'=>now()]);
  return $id;
 }
 public function sale(\App\Models\Sale $sale):void {
  $total=(int)round($sale->total*100);$tax=(int)round($sale->tax_total*100);
  $payment=in_array($sale->payment_method,['cash','Cash'])?'1000':'1010';
  $lines=[['code'=>$payment,'debit'=>$total],['code'=>'4000','credit'=>$total-$tax]];
  if($tax>0)$lines[]=['code'=>'2100','credit'=>$tax];
  $this->post($sale->store_id,'sale',$sale->id,'POS '.$sale->invoice_no,$lines);
 }
 public function purchase(\App\Models\Purchase $purchase):void {
  $total=(int)round($purchase->total*100);$tax=(int)round($purchase->tax_total*100);
  $lines=[['code'=>'5000','debit'=>$total-$tax],['code'=>'2000','credit'=>$total]];
  if($tax>0)$lines[]=['code'=>'2200','debit'=>$tax];
  $this->post($purchase->store_id,'purchase',$purchase->id,'Purchase '.$purchase->invoice_no,$lines);
 }
 public function supplierPayment(\App\Models\Purchase $purchase,int $amountPaise,string $method,?string $reference,int $userId):void {
  if($amountPaise<=0) throw ValidationException::withMessages(['amount'=>'Payment must be positive.']);
  $paid=(int)round(DB::table('supplier_payments')->where('purchase_id',$purchase->id)->sum('amount')*100);
  // Credits from returns are displayed separately; offset settlement is not yet implemented.
  if($paid+$amountPaise>(int)round($purchase->total*100)) throw ValidationException::withMessages(['amount'=>'Payment exceeds purchase invoice outstanding.']);
  $id=DB::table('supplier_payments')->insertGetId(['purchase_id'=>$purchase->id,'supplier_id'=>$purchase->supplier_id,'store_id'=>$purchase->store_id,'amount'=>$amountPaise/100,'method'=>$method,'reference_no'=>$reference,'created_by'=>$userId,'created_at'=>now(),'updated_at'=>now()]);
  $this->post($purchase->store_id,'supplier_payment',$id,'Supplier payment for purchase #'.$purchase->id,[['code'=>'2000','debit'=>$amountPaise],['code'=>$method==='cash'?'1000':'1010','credit'=>$amountPaise]],$userId);
 }
}