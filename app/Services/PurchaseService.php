<?php
namespace App\Services;

use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\MedicinePacking;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function __construct(private InventoryService $inventory, private AccountingService $accounting) {}

    public function post(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $subtotal = 0;
            $discountTotal = 0;
            $taxTotal = 0;
            $prepared = [];
            foreach ($data['items'] as $index => $row) {
                $medicine = Medicine::findOrFail($row['medicine_id']);
                $packing = MedicinePacking::where('medicine_id', $medicine->id)
                    ->where('is_active', true)->findOrFail($row['medicine_packing_id']);
                $units = (int) $packing->units_per_pack;
                if ($units < 1) {
                    throw ValidationException::withMessages(["items.$index.medicine_packing_id" => 'Invalid packing conversion.']);
                }
                $paid = (int) $row['packs_purchased'];
                $free = (int) ($row['free_packs'] ?? 0);
                $qty = $paid * $units;
                $freeQty = $free * $units;
                if ($qty < 1 || $qty > 100000000 || $freeQty > 100000000) {
                    throw ValidationException::withMessages(["items.$index.packs_purchased" => 'Invalid stock quantity.']);
                }
                if ($row['expiry_date'] < now()->startOfMonth()->toDateString()) {
                    throw ValidationException::withMessages(["items.$index.expiry_date" => 'Cannot receive an expired batch.']);
                }
                $gross = round($paid * (float)$row['cost_per_pack'], 2);
                $discount = round($gross * (float)($row['discount_percent'] ?? 0) / 100, 2);
                $taxable = round($gross - $discount, 2);
                // Illustrative tax rate supplied by operator. Verify applicable GST before production use.
                $rate = (float)($row['tax_rate'] ?? 0);
                $tax = round($taxable * $rate / 100, 2);
                $subtotal += $gross;
                $discountTotal += $discount;
                $taxTotal += $tax;
                $prepared[] = compact('row','medicine','packing','qty','freeQty','paid','gross','discount','taxable','rate','tax');
            }
            $purchase = Purchase::create([
                'store_id'=>\App\Support\StoreContext::id(),
                'supplier_id'=>$data['supplier_id'],
                'invoice_no'=>$data['invoice_no'],
                'invoice_date'=>$data['invoice_date'],
                'subtotal'=>round($subtotal,2),
                'discount_total'=>round($discountTotal,2),
                'tax_total'=>round($taxTotal,2),
                'total'=>round($subtotal-$discountTotal+$taxTotal,2),
                'status'=>'posted',
            ]);
            foreach ($prepared as $line) {
                extract($line);
                $batch = MedicineBatch::where('medicine_id',$medicine->id)
                    ->where('batch_no',$row['batch_no'])
                    ->whereDate('expiry_date',$row['expiry_date'])
                    ->lockForUpdate()->first();
                $mrpUnit = round((float)$row['mrp_per_pack'] / $packing->units_per_pack, 2);
                $saleUnit = round((float)$row['sale_price_per_pack'] / $packing->units_per_pack, 2);
                if ($saleUnit > $mrpUnit) {
                    throw ValidationException::withMessages(['items'=>'Selling price exceeds MRP.']);
                }
                if ($batch && (abs((float)$batch->mrp_per_unit - $mrpUnit) > .009 || abs((float)$batch->sale_price_per_unit - $saleUnit) > .009)) {
                    throw ValidationException::withMessages(['items'=>'Existing batch has different MRP / selling rate; revise master pricing before receiving.']);
                }
                $batch ??= MedicineBatch::create([
                    'medicine_id'=>$medicine->id, 'batch_no'=>$row['batch_no'],
                    'expiry_date'=>$row['expiry_date'], 'mrp_per_unit'=>$mrpUnit,
                    'sale_price_per_unit'=>$saleUnit,
                ]);
                $item = PurchaseItem::create([
                    'purchase_id'=>$purchase->id, 'medicine_batch_id'=>$batch->id,
                    'medicine_packing_id'=>$packing->id, 'packs_purchased'=>$paid,
                    'quantity'=>$qty + $freeQty, 'free_quantity'=>$freeQty,
                    'cost_per_unit'=>round(($gross-$discount)/$qty,2),
                    'discount_percent'=>$row['discount_percent'] ?? 0,
                    'discount_amount'=>$discount, 'tax_rate'=>$rate,
                    'tax_amount'=>$tax, 'taxable_amount'=>$taxable,
                    'line_total'=>round($taxable+$tax,2),
                ]);
                $this->inventory->add(\App\Support\StoreContext::id(),$batch->id,$qty+$freeQty,'purchase','purchase_items',$item->id);
            }
            $this->accounting->purchase($purchase);
            return $purchase;
        }, 3);
    }
}
