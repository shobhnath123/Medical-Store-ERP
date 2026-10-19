<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Services\StockTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class CompanyStoresController extends Controller {
 // V10 admin-only setup is deliberately locked to legacy demo company 1.
 // Cross-company tenant access is NOT enabled in this version.
 private function companyId():int{return \App\Support\StoreContext::companyId();}
 public function index(){
  $company=DB::table('companies')->where('id',$this->companyId())->first();
  $stores=DB::table('stores')->where('company_id',$company->id)->orderBy('id')->get();
  $balances=DB::table('stock_balances as sb')->join('stores as s','s.id','=','sb.store_id')->join('medicine_batches as b','b.id','=','sb.medicine_batch_id')->join('medicines as m','m.id','=','b.medicine_id')->where('s.company_id',$company->id)->where('sb.quantity','>',0)->select('sb.store_id','sb.medicine_batch_id','sb.quantity','m.name','b.batch_no')->orderBy('m.name')->limit(250)->get();
  $transfers=DB::table('stock_transfers as t')->join('stores as fs','fs.id','=','t.from_store_id')->join('stores as ts','ts.id','=','t.to_store_id')->where('t.company_id',$company->id)->select('t.*','fs.name as from_name','ts.name as to_name')->latest('t.id')->limit(30)->get();
  $plans=DB::table('subscription_plans')->orderBy('id')->get();
  return view('admin.stores.index',compact('company','stores','balances','transfers','plans'));
 }
 public function store(Request $r){
  $data=$r->validate(['name'=>'required|string|max:255','code'=>['required','alpha_dash','max:40',Rule::unique('stores')->where('company_id',$this->companyId())]]);
  DB::table('stores')->insert(['company_id'=>$this->companyId(),'name'=>$data['name'],'code'=>strtoupper($data['code']),'is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);
  return back()->with('success','Branch created. Assign user membership before use.');
 }
 public function plan(Request $r){
  $data=$r->validate(['name'=>'required|string|max:80','max_stores'=>'required|integer|min:1|max:1000','price'=>'required|numeric|min:0|max:999999']);
  DB::table('subscription_plans')->insert($data+['is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);
  return back()->with('success','Subscription plan defined (no subscription enforcement or payment integration yet).');
 }
 public function transfer(Request $r,StockTransferService $svc){
  $data=$r->validate(['from_store_id'=>'required|integer|exists:stores,id','to_store_id'=>'required|integer|different:from_store_id|exists:stores,id','medicine_batch_id'=>'required|integer|exists:medicine_batches,id','quantity'=>'required|integer|min:1|max:1000000','note'=>'nullable|string|max:500']);
  $svc->post($this->companyId(),$data['from_store_id'],$data['to_store_id'],$data['medicine_batch_id'],$data['quantity'],$r->user()->id,$data['note']??null);
  return back()->with('success','Transfer posted. Stock moved between two company branches.');
 }
}