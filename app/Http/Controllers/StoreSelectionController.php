<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class StoreSelectionController extends Controller {
 private function stores(Request $request) {
  return DB::table('stores as s')->join('store_user as su','su.store_id','=','s.id')
    ->join('companies as c','c.id','=','s.company_id')
    ->where('su.user_id',$request->user()->id)
    ->where('s.is_active',1)->where('c.is_active',1)
    ->select('s.id','s.name','s.code')->orderBy('s.name')->get();
 }
 public function index(Request $request){return view('auth.select-store',['stores'=>$this->stores($request)]);}
 public function select(Request $request){
  $data=$request->validate(['store_id'=>'required|integer']);
  abort_unless($this->stores($request)->contains('id',(int)$data['store_id']),403);
  $request->session()->regenerate();
  $request->session()->put('pharmacy_store_id',(int)$data['store_id']);
  return redirect()->route('dashboard');
 }
}
