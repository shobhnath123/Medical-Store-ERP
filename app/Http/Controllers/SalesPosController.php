<?php
namespace App\Http\Controllers;
use App\Models\Sale;
use App\Models\StockBalance;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalesPosController extends Controller {
 public function index(){
  $batches=StockBalance::query()->join('medicine_batches as b','b.id','=','stock_balances.medicine_batch_id')
   ->join('medicines as m','m.id','=','b.medicine_id')
   ->where('stock_balances.store_id',1)->where('stock_balances.quantity','>',0)
   ->whereDate('b.expiry_date','>=',now()->startOfMonth()->toDateString())
   ->where('m.is_active',true)
   ->select('b.id','m.name','b.batch_no','b.expiry_date','b.sale_price_per_unit','b.mrp_per_unit','stock_balances.quantity')
   ->orderBy('m.name')->orderBy('b.expiry_date')->get();
  return view('sales.index',['batches'=>$batches,'rows'=>Sale::latest()->limit(30)->get()]);
 }
 public function store(Request $request,BillingService $service){
  $data=$request->validate([
   'customer_name'=>'nullable|string|max:255',
   'payment_method'=>['required',Rule::in(['cash','upi','card'])],
   'payment_reference'=>'nullable|string|max:100',
   'discount'=>'nullable|numeric|min:0|max:99999999',
   'items'=>'required|array|min:1|max:50',
   'items.*.batch_id'=>'required|integer|distinct|exists:medicine_batches,id',
   'items.*.quantity'=>'required|integer|min:1|max:1000000',
  ]);
  $sale=$service->post($data);
  return redirect()->route('sales.show',$sale)->with('success','Invoice posted and stock deducted');
 }
 public function show(Sale $sale){
  abort_unless($sale->store_id===1,404); // replace with store-scoped policy before production
  $items=$sale->hasMany(\App\Models\SaleItem::class)->with('batch.medicine')->get();
  return view('sales.invoice',compact('sale','items'));
 }
}
