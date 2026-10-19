<?php
namespace App\Services;
use App\Models\{MedicineBatch,MedicinePacking,Sale,SaleItem,StockBalance};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class BillingService {
 public function __construct(private InventoryService $inventory, private AccountingService $accounting) {}
 public function post(array $data): Sale {
  return DB::transaction(function () use ($data) {
   $storeId=\App\Support\StoreContext::id();
   $lines=[];$subtotalCents=0;
   foreach($data['items'] as $index=>$row){
    $batch=MedicineBatch::with('medicine.taxCode')->findOrFail($row['batch_id']);
    $packing=MedicinePacking::findOrFail($row['packing_id']);
    if ($packing->medicine_id!==$batch->medicine_id || !$packing->is_active || $packing->units_per_pack<1) {
     throw ValidationException::withMessages(["items.$index.packing_id"=>'Invalid medicine packing.']);
    }
    if (!$batch->medicine->is_active || $batch->medicine->prescription_required || $batch->medicine->schedule_type) {
     throw ValidationException::withMessages(["items.$index.batch_id"=>'This medicine requires pharmacist / regulatory checks unavailable in this demo.']);
    }
    if ($batch->expiry_date->lt(now()->startOfMonth())) {
     throw ValidationException::withMessages(["items.$index.batch_id"=>'Expired batch.']);
    }
    $baseQty=(int)$row['quantity']*(int)$packing->units_per_pack;
    if ($baseQty<1 || $baseQty>1000000000) throw ValidationException::withMessages(["items.$index.quantity"=>'Quantity out of range.']);
    $balance=StockBalance::where('store_id',$storeId)->where('medicine_batch_id',$batch->id)->lockForUpdate()->first();
    if (!$balance || $balance->quantity<$baseQty) throw ValidationException::withMessages(["items.$index.quantity"=>'Insufficient batch stock.']);
    $unitCents=(int)round((float)$batch->sale_price_per_unit*100);
    $mrpCents=(int)round((float)$batch->mrp_per_unit*100);
    if ($unitCents<0 || $unitCents>$mrpCents) throw ValidationException::withMessages(["items.$index.batch_id"=>'Invalid price / MRP.']);
    $rate=(float)($batch->medicine->taxCode?->illustrative_rate ?? 0);
    if ($rate<0 || $rate>100) throw ValidationException::withMessages(["items.$index.batch_id"=>'Invalid demo tax rate.']);
    $gross=$unitCents*$baseQty;$subtotalCents+=$gross;
    $lines[]=['batch'=>$batch,'packing'=>$packing,'pack_qty'=>(int)$row['quantity'],'base_qty'=>$baseQty,'gross'=>$gross,'unit_cents'=>$unitCents,'rate'=>$rate];
   }
   $discountCents=(int)round((float)($data['discount']??0)*100);
   if ($discountCents>$subtotalCents) throw ValidationException::withMessages(['discount'=>'Discount exceeds subtotal.']);
   $sale=Sale::create(['store_id'=>$storeId,'customer_name'=>$data['customer_name']??null,'payment_method'=>$data['payment_method'],'payment_reference'=>$data['payment_reference']??null,'subtotal'=>0,'discount_total'=>0,'total'=>0,'status'=>'posting']);
   $remainingDiscount=$discountCents;$taxCents=0;$taxableCents=0;
   foreach($lines as $i=>$line){
    $allocated=$i===count($lines)-1?$remainingDiscount:($subtotalCents? min($remainingDiscount,(int)round($discountCents*$line['gross']/$subtotalCents)):0);
    $remainingDiscount-=$allocated;
    $net=$line['gross']-$allocated;
    // Illustrative tax-inclusive breakdown. Uses integer cents after rounding.
    $base=(int)round($net/(1+$line['rate']/100));
    $tax=$net-$base;$taxCents+=$tax;$taxableCents+=$base;
    $item=SaleItem::create([
     'sale_id'=>$sale->id,'medicine_batch_id'=>$line['batch']->id,
     'medicine_packing_id'=>$line['packing']->id,'pack_quantity'=>$line['pack_qty'],
     'units_per_pack'=>$line['packing']->units_per_pack,'quantity'=>$line['base_qty'],
     'unit_price'=>$line['unit_cents']/100,'discount_amount'=>$allocated/100,
     'tax_rate'=>$line['rate'],'taxable_amount'=>$base/100,'tax_amount'=>$tax/100,
     'hsn_code'=>$line['batch']->medicine->hsn_code,'line_total'=>$net/100,
    ]);
    $this->inventory->deduct($storeId,$line['batch']->id,$line['base_qty'],'sale_items',$item->id);
   }
   $sale->update(['invoice_no'=>sprintf('POS-%06d',$sale->id),'subtotal'=>$subtotalCents/100,
    'discount_total'=>$discountCents/100,'taxable_total'=>$taxableCents/100,'tax_total'=>$taxCents/100,
    'tax_mode'=>'inclusive','total'=>($subtotalCents-$discountCents)/100,'status'=>'posted']);
   $this->accounting->sale($sale);
   return $sale;
  },3);
 }
}
