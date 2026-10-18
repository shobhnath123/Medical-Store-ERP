<?php
namespace App\Services;
use App\Models\MedicineBatch;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockBalance;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BillingService {
 public function __construct(private InventoryService $inventory) {}
 public function post(array $data): Sale {
  return DB::transaction(function () use ($data) {
   $storeId = 1; // DEMO ONLY: replace with user's authorized store context.
   $sale = Sale::create([
    'store_id'=>$storeId,'customer_name'=>$data['customer_name']??null,
    'payment_method'=>$data['payment_method'],'payment_reference'=>$data['payment_reference']??null,
    'total'=>0,'subtotal'=>0,'discount_total'=>0,'status'=>'posting',
   ]);
   $subtotalCents = 0;
   $lines = [];
   // Recheck all prices and batches on the server, regardless of browser values.
   foreach ($data['items'] as $index=>$row) {
    $qty=(int)$row['quantity'];
    $batch=MedicineBatch::findOrFail($row['batch_id']);
    // Pharmacy packs typically state MM/YYYY; expiry_date is stored as month end.
    if ($batch->expiry_date->lt(now()->startOfMonth())) {
     throw ValidationException::withMessages(["items.$index.batch_id"=>'Expired batch cannot be sold.']);
    }
    $balance=StockBalance::where('store_id',$storeId)->where('medicine_batch_id',$batch->id)->lockForUpdate()->first();
    if (!$balance || $balance->quantity<$qty) {
     throw ValidationException::withMessages(["items.$index.quantity"=>'Insufficient stock for selected batch.']);
    }
    $unitCents=(int)round(((float)$batch->sale_price_per_unit)*100);
    $mrpCents=(int)round(((float)$batch->mrp_per_unit)*100);
    if($unitCents<0 || $unitCents>$mrpCents){
     throw ValidationException::withMessages(["items.$index.batch_id"=>'Batch has invalid selling price.']);
    }
    $gross=$qty*$unitCents;
    $subtotalCents+=$gross;
    $lines[]=['batch'=>$batch,'quantity'=>$qty,'unit_cents'=>$unitCents,'gross_cents'=>$gross];
   }
   // Fixed invoice-wide discount, not a tax calculation. GST engine is pending.
   $discountCents=(int)round(((float)($data['discount']??0))*100);
   if($discountCents<0 || $discountCents>$subtotalCents){
    throw ValidationException::withMessages(['discount'=>'Discount exceeds invoice subtotal.']);
   }
   $remainingDiscount=$discountCents;
   foreach ($lines as $i=>$line) {
    $isLast=$i===count($lines)-1;
    $allocated=$isLast ? $remainingDiscount : min($remainingDiscount,(int)round($discountCents*$line['gross_cents']/$subtotalCents));
    $remainingDiscount-=$allocated;
    $item=SaleItem::create([
     'sale_id'=>$sale->id,'medicine_batch_id'=>$line['batch']->id,
     'quantity'=>$line['quantity'],'unit_price'=>$line['unit_cents']/100,
     'discount_amount'=>$allocated/100,'line_total'=>($line['gross_cents']-$allocated)/100,
    ]);
    $this->inventory->deduct($storeId,$line['batch']->id,$line['quantity'],'sale_items',$item->id);
   }
   $sale->update([
    'invoice_no'=>sprintf('POS-%06d',$sale->id),
    'subtotal'=>$subtotalCents/100,'discount_total'=>$discountCents/100,
    'total'=>($subtotalCents-$discountCents)/100,'status'=>'posted'
   ]);
   return $sale;
  },3);
 }
}
