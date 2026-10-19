<?php
namespace App\Http\Controllers;
use App\Models\Purchase;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class AccountingController extends Controller {
 public function index(Request $request){
  $storeId=\App\Support\StoreContext::id();
  $accounts=DB::table('accounts')->where('store_id',$storeId)->orderBy('code')->get();
  $entries=DB::table('journal_entries')->where('store_id',$storeId)->orderByDesc('id')->limit(40)->get();
  $trial=DB::table('journal_lines as l')->join('journal_entries as j','j.id','=','l.journal_entry_id')->join('accounts as a','a.id','=','l.account_id')->where('j.store_id',$storeId)->select('a.code','a.name','a.type')->selectRaw('ROUND(SUM(l.debit),2) as debit')->selectRaw('ROUND(SUM(l.credit),2) as credit')->groupBy('a.id','a.code','a.name','a.type')->orderBy('a.code')->get();
  $purchases=Purchase::where('store_id',$storeId)->where('status','posted')->orderByDesc('id')->limit(100)->get();
  $payments=DB::table('supplier_payments')->where('store_id',$storeId)->select('purchase_id')->selectRaw('SUM(amount) as paid')->groupBy('purchase_id')->pluck('paid','purchase_id');
  $sales=DB::table('sales')->where('store_id',$storeId)->where('status','posted')->selectRaw('COUNT(*) as count,COALESCE(SUM(total),0) as total')->first();
  $unpostedSales=DB::table('sales as s')->leftJoin('journal_entries as j',function($join){$join->on('j.reference_id','=','s.id')->where('j.reference_type','sale');})->where('s.store_id',$storeId)->where('s.status','posted')->whereNull('j.id')->count();
  $unpostedPurchases=DB::table('purchases as p')->leftJoin('journal_entries as j',function($join){$join->on('j.reference_id','=','p.id')->where('j.reference_type','purchase');})->where('p.store_id',$storeId)->where('p.status','posted')->whereNull('j.id')->count();
  return view('accounting.index',compact('accounts','entries','trial','purchases','payments','sales','unpostedSales','unpostedPurchases'));
 }
 public function paySupplier(Request $request,AccountingService $accounting){
  $data=$request->validate(['purchase_id'=>'required|integer|exists:purchases,id','amount'=>'required|numeric|gt:0|decimal:0,2','method'=>'required|in:cash,bank','reference_no'=>'nullable|string|max:100']);
  $purchase=Purchase::where('store_id',\App\Support\StoreContext::id())->where('status','posted')->findOrFail($data['purchase_id']);
  DB::transaction(function() use($accounting,$purchase,$data){
    Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();
    $accounting->supplierPayment($purchase,(int)round((float)$data['amount']*100),$data['method'],$data['reference_no']??null,auth()->id());
  },3);
  return redirect()->route('accounting.index')->with('success','Supplier payment posted to ledger.');
 }
}