<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
class PharmacyUserController extends Controller {
 public function index(){return view('admin.users.index',['users'=>User::query()->whereIn('id',\DB::table('store_user as su')->join('stores as s','s.id','=','su.store_id')->where('s.company_id',\App\Support\StoreContext::companyId())->select('su.user_id'))->orderBy('name')->get()]);}
 public function store(Request $r){
  $data=$r->validate(['name'=>['required','string','max:255'],'email'=>['required','email','max:255','unique:users,email'],'password'=>['required','string','min:12'],'role'=>['required',Rule::in(['admin','manager','pharmacist','cashier'])]]);
  $user=new User();$user->name=$data['name'];$user->email=$data['email'];$user->password=Hash::make($data['password']);$user->role=$data['role'];$user->is_active=true;$user->save();
  \DB::table('store_user')->insert(['store_id'=>\App\Support\StoreContext::id(),'user_id'=>$user->id,'created_at'=>now(),'updated_at'=>now()]);
  return back()->with('success','User created');
 }

 public function assignStore(Request $r) {
  $d=$r->validate(['user_id'=>'required|integer|exists:users,id','store_id'=>'required|integer']);
  abort_unless(\DB::table('store_user as su')->join('stores as s','s.id','=','su.store_id')->where('su.user_id',$d['user_id'])->where('s.company_id',\App\Support\StoreContext::companyId())->exists(),403);
  abort_unless(\DB::table('stores')->where('id',$d['store_id'])->where('company_id',\App\Support\StoreContext::companyId())->where('is_active',1)->exists(),403);
  \DB::table('store_user')->insertOrIgnore(['user_id'=>$d['user_id'],'store_id'=>$d['store_id'],'created_at'=>now(),'updated_at'=>now()]);
  return back()->with('success','Store membership assigned.');
 }
}
