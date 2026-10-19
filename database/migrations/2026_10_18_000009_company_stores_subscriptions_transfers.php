<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('companies',function(Blueprint $t){$t->id();$t->string('name');$t->string('code',40)->unique();$t->string('gstin',15)->nullable();$t->string('drug_license_no',100)->nullable();$t->boolean('is_active')->default(true);$t->timestamps();});
  DB::table('companies')->insert(['id'=>1,'name'=>'Demo Pharmacy Company','code'=>'DEMO','created_at'=>now(),'updated_at'=>now()]);
  Schema::table('stores',function(Blueprint $t){$t->foreignId('company_id')->default(1)->constrained('companies')->restrictOnDelete();$t->string('code',40)->nullable();$t->boolean('is_active')->default(true);});
  DB::table('stores')->whereNull('code')->update(['code'=>'MAIN']);
  Schema::table('stores',function(Blueprint $t){$t->unique(['company_id','code']);});
  Schema::create('store_user',function(Blueprint $t){$t->id();$t->foreignId('store_id')->constrained()->cascadeOnDelete();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->timestamps();$t->unique(['store_id','user_id']);});
  DB::table('store_user')->insertUsing(['store_id','user_id','created_at','updated_at'],DB::table('users')->selectRaw('1, id, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP'));
  Schema::create('subscription_plans',function(Blueprint $t){$t->id();$t->string('name');$t->unsignedInteger('max_stores')->default(1);$t->decimal('price',12,2)->default(0);$t->boolean('is_active')->default(true);$t->timestamps();});
  Schema::create('company_subscriptions',function(Blueprint $t){$t->id();$t->foreignId('company_id')->constrained()->cascadeOnDelete();$t->foreignId('subscription_plan_id')->constrained('subscription_plans');$t->string('status',20)->default('trial');$t->date('starts_on');$t->date('ends_on');$t->timestamps();});
  Schema::create('stock_transfers',function(Blueprint $t){$t->id();$t->foreignId('company_id')->constrained();$t->foreignId('from_store_id')->constrained('stores');$t->foreignId('to_store_id')->constrained('stores');$t->foreignId('created_by')->constrained('users');$t->string('status',20)->default('posted');$t->string('reference_no',50)->unique();$t->text('note')->nullable();$t->timestamps();});
  Schema::create('stock_transfer_items',function(Blueprint $t){$t->id();$t->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();$t->foreignId('medicine_batch_id')->constrained();$t->unsignedBigInteger('quantity');$t->timestamps();});
 }
 public function down(): void {
  Schema::dropIfExists('stock_transfer_items');Schema::dropIfExists('stock_transfers');Schema::dropIfExists('company_subscriptions');Schema::dropIfExists('subscription_plans');Schema::dropIfExists('store_user');
  Schema::table('stores',fn(Blueprint $t)=>$t->dropUnique(['company_id','code']));
  Schema::table('stores',fn(Blueprint $t)=>$t->dropConstrainedForeignId('company_id'));
  Schema::table('stores',fn(Blueprint $t)=>$t->dropColumn(['code','is_active']));
  Schema::dropIfExists('companies');
 }
};