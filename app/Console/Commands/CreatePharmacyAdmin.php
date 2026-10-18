<?php
namespace App\Console\Commands;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
class CreatePharmacyAdmin extends Command {
 protected $signature='pharmacy:create-admin';
 protected $description='Interactively create the initial pharmacy administrator';
 public function handle(): int {
  $name=$this->ask('Administrator name');$email=$this->ask('Administrator email');$password=$this->secret('Strong password (12+ characters)');
  $v=Validator::make(compact('name','email','password'),['name'=>'required|string|max:255','email'=>'required|email|unique:users,email','password'=>'required|string|min:12']);
  if($v->fails()){foreach($v->errors()->all() as $e)$this->error($e);return self::FAILURE;}
  $u=new User();$u->name=$name;$u->email=$email;$u->password=Hash::make($password);$u->role='admin';$u->is_active=true;$u->save();
  $this->info('Admin created. Log in with these credentials.');return self::SUCCESS;
 }
}
