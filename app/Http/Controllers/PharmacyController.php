<?php
namespace App\Http\Controllers;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockBalance;
use App\Services\PurchaseService;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class PharmacyController extends Controller {
 public function home(){return view('dashboard',['medicineCount'=>Medicine::count(),'supplierCount'=>Supplier::count(),'stock'=>StockBalance::sum('quantity'),'salesTotal'=>Sale::sum('total')]);}
 public function medicines(){return view('medicines.index',['rows'=>Medicine::latest()->get()]);}
 public function medicineStore(Request $r){Medicine::create($r->validate(['name'=>'required|string|max:255','generic_name'=>'nullable|string|max:255','units_per_strip'=>'required|integer|min:1|max:1000']));return back()->with('success','Medicine created');}
 public function suppliers(){return view('suppliers.index',['rows'=>Supplier::latest()->get()]);}
 public function supplierStore(Request $r){Supplier::create($r->validate(['name'=>'required|string|max:255','phone'=>'nullable|string|max:20','gstin'=>'nullable|string|max:15']));return back()->with('success','Supplier created');}
 public function purchases(){return view('purchases.index',['rows'=>Purchase::latest()->limit(50)->get(),'medicineOptions'=>Medicine::with('packings')->orderBy('name')->get()->map(fn($m) => ['id'=>$m->id,'name'=>$m->name,'packings'=>$m->packings->where('is_active',true)->map(fn($p)=>['id'=>$p->id,'label'=>$p->label,'units'=>$p->units_per_pack])->values()]),'suppliers'=>Supplier::orderBy('name')->get()]);}
 public function purchaseStore(Request $r,PurchaseService $svc){
  $data=$r->validate([
   'supplier_id'=>'required|exists:suppliers,id',
   'invoice_no'=>'required|string|max:100',
   'invoice_date'=>'required|date',
   'items'=>'required|array|min:1|max:30',
   'items.*.medicine_id'=>'required|exists:medicines,id',
   'items.*.medicine_packing_id'=>'required|exists:medicine_packings,id',
   'items.*.batch_no'=>'required|string|max:80',
   'items.*.expiry_date'=>'required|date',
   'items.*.packs_purchased'=>'required|integer|min:1|max:1000000',
   'items.*.free_packs'=>'nullable|integer|min:0|max:1000000',
   'items.*.cost_per_pack'=>'required|numeric|min:0|max:99999999',
   'items.*.mrp_per_pack'=>'required|numeric|min:0.01|max:99999999',
   'items.*.sale_price_per_pack'=>'required|numeric|min:0|max:99999999',
   'items.*.discount_percent'=>'nullable|numeric|min:0|max:100',
   'items.*.tax_rate'=>'nullable|numeric|min:0|max:100',
  ]);
  $svc->post($data);
  return redirect()->route('purchases.index')->with('success','Multi-item purchase posted and batch inventory updated');
 }
 public function inventory(){return view('inventory.index',['rows'=>StockBalance::query()->join('medicine_batches','medicine_batches.id','=','stock_balances.medicine_batch_id')->join('medicines','medicines.id','=','medicine_batches.medicine_id')->select('stock_balances.*','medicines.name as medicine_name','medicine_batches.batch_no','medicine_batches.expiry_date')->orderBy('medicines.name')->get()]);}
 public function sales(){return view('sales.index',['rows'=>Sale::latest()->get(),'batches'=>StockBalance::query()->join('medicine_batches','medicine_batches.id','=','stock_balances.medicine_batch_id')->join('medicines','medicines.id','=','medicine_batches.medicine_id')->where('stock_balances.quantity','>',0)->whereDate('medicine_batches.expiry_date','>=',today())->select('medicine_batches.id','medicines.name','medicine_batches.batch_no','medicine_batches.sale_price_per_unit','stock_balances.quantity')->orderBy('medicines.name')->get()]);}
 public function saleStore(Request $r,BillingService $svc){$data=$r->validate(['batch_id'=>'required|exists:medicine_batches,id','quantity'=>'required|integer|min:1','customer_name'=>'nullable|string|max:255','payment_method'=>['required',Rule::in(['cash','upi','card'])]]);$svc->post($data);return back()->with('success','Sale posted and stock deducted');}
}
