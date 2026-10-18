<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
class PharmacyUserController extends Controller {
 public function index(){return view('admin.users.index',['users'=>User::query()->orderBy('name')->get()]);}
 public function store(Request $r){
  $data=$r->validate(['name'=>['required','string','max:255'],'email'=>['required','email','max:255','unique:users,email'],'password'=>['required','string','min:12'],'role'=>['required',Rule::in(['admin','manager','pharmacist','cashier'])]]);
  $user=new User();$user->name=$data['name'];$user->email=$data['email'];$user->password=Hash::make($data['password']);$user->role=$data['role'];$user->is_active=true;$user->save();
  return back()->with('success','User created');
 }
}
