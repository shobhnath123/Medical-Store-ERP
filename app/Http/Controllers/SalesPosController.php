<?php
namespace App\Http\Controllers;
use App\Models\{Sale,StockBalance,MedicinePacking};
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class SalesPosController extends Controller {
 public function index(){
  $batches=StockBalance::query()->join('medicine_batches as b','b.id','=','stock_balances.medicine_batch_id')
   ->join('medicines as m','m.id','=','b.medicine_id')
   ->leftJoin('tax_codes as t','t.id','=','m.tax_code_id')
   ->where('stock_balances.store_id',\App\Support\StoreContext::id())->where('stock_balances.quantity','>',0)
   ->whereDate('b.expiry_date','>=',now()->startOfMonth()->toDateString())
   ->where('m.is_active',true)->where('m.prescription_required',false)
   ->select('b.id','b.medicine_id','m.name','b.batch_no','b.expiry_date','b.sale_price_per_unit','b.mrp_per_unit','stock_balances.quantity','t.illustrative_rate as tax_rate')
   ->orderBy('m.name')->orderBy('b.expiry_date')->get();
  $packings=MedicinePacking::where('is_active',true)->where('units_per_pack','>',0)
    ->get(['id','medicine_id','label','units_per_pack','barcode']);
  return view('sales.index',['batches'=>$batches,'packings'=>$packings,'rows'=>Sale::where('store_id',\App\Support\StoreContext::id())->latest()->limit(30)->get()]);
 }
 public function store(Request $request,BillingService $service){
  $data=$request->validate([
   'customer_name'=>'nullable|string|max:255',
   'payment_method'=>['required',Rule::in(['cash','upi','card'])],
   'payment_reference'=>'nullable|string|max:100',
   'discount'=>'nullable|numeric|min:0|max:99999999',
   'items'=>'required|array|min:1|max:50',
   'items.*.batch_id'=>'required|integer|distinct|exists:medicine_batches,id',
   'items.*.packing_id'=>'required|integer|exists:medicine_packings,id',
   'items.*.quantity'=>'required|integer|min:1|max:1000000',
  ]);
  $sale=$service->post($data);
  return redirect()->route('sales.show',$sale)->with('success','Demo invoice posted and stock deducted');
 }
 public function show(Sale $sale){
  abort_unless((int)$sale->store_id===\App\Support\StoreContext::id(),404);
  $items=$sale->hasMany(\App\Models\SaleItem::class)->with('batch.medicine')->get();
  return view('sales.invoice',compact('sale','items'));
 }
}
