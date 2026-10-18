<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
class PharmacyLoginController extends Controller {
 public function show(){return view('auth.login');}
 public function login(Request $request){
  $data=$request->validate(['email'=>['required','email'],'password'=>['required','string']]);
  if(!Auth::attempt([...$data,'is_active'=>true],$request->boolean('remember'))){throw ValidationException::withMessages(['email'=>'Invalid credentials or inactive account.']);}
  $request->session()->regenerate();
  return redirect()->intended(route('dashboard'));
 }
 public function logout(Request $request){Auth::logout();$request->session()->invalidate();$request->session()->regenerateToken();return redirect()->route('login');}
}
