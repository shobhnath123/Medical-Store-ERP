<?php
namespace App\Http\Controllers;
use App\Models\{Sale,Purchase,MedicineBatch};
use App\Services\ReturnService;
use Illuminate\Http\Request;
class ReturnsController extends Controller {
 public function index(){return view('returns.index',[
 'sales'=>Sale::where('status','posted')->latest()->limit(80)->get(),
 'saleItems'=>\DB::table('sale_items')->join('sales','sales.id','=','sale_items.sale_id')->where('sales.status','posted')->select('sale_items.id','sale_items.sale_id','sale_items.quantity')->latest('sale_items.id')->limit(300)->get(),
 'purchases'=>Purchase::where('status','posted')->latest()->limit(80)->get(),
 'purchaseItems'=>\DB::table('purchase_items')->join('purchases','purchases.id','=','purchase_items.purchase_id')->where('purchases.status','posted')->select('purchase_items.id','purchase_items.purchase_id','purchase_items.quantity')->latest('purchase_items.id')->limit(300)->get(),
 'batches'=>MedicineBatch::with('medicine')->latest()->limit(200)->get(),
 'salesReturns'=>\DB::table('sales_returns')->latest('id')->limit(30)->get(),
 'purchaseReturns'=>\DB::table('purchase_returns')->latest('id')->limit(30)->get(),
 'adjustments'=>\DB::table('stock_adjustments')->latest('id')->limit(30)->get()]); }
 public function sale(Request $r,ReturnService $service){$data=$r->validate(['sale_id'=>'required|exists:sales,id','sale_item_id'=>'required|exists:sale_items,id','quantity'=>'required|integer|min:1|max:1000000','reason'=>'required|string|max:255']);$service->salesReturn($data,$r->user()->id);return back()->with('success','Customer return recorded in quarantine; refund pending.');}
 public function purchase(Request $r,ReturnService $service){$data=$r->validate(['purchase_id'=>'required|exists:purchases,id','purchase_item_id'=>'required|exists:purchase_items,id','quantity'=>'required|integer|min:1|max:1000000','reason'=>'required|string|max:255']);$service->purchaseReturn($data,$r->user()->id);return back()->with('success','Supplier return recorded, stock deducted; credit pending.');}
 public function adjustment(Request $r,ReturnService $service){$data=$r->validate(['medicine_batch_id'=>'required|exists:medicine_batches,id','quantity_change'=>'required|integer|between:-1000000,1000000|not_in:0','reason'=>'required|string|max:255']);$service->adjust($data,$r->user()->id);return back()->with('success','Audited stock adjustment posted.');}
}