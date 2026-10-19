<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up(): void {
  Schema::create('accounts',function(Blueprint $t){$t->id();$t->foreignId('store_id')->constrained();$t->string('code',25);$t->string('name');$t->string('type',25);$t->boolean('is_active')->default(true);$t->timestamps();$t->unique(['store_id','code']);});
  Schema::create('journal_entries',function(Blueprint $t){$t->id();$t->foreignId('store_id')->constrained();$t->date('entry_date');$t->string('reference_type',70);$t->unsignedBigInteger('reference_id');$t->string('description',255);$t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();$t->timestamps();$t->unique(['store_id','reference_type','reference_id'],'unique_journal_source');});
  Schema::create('journal_lines',function(Blueprint $t){$t->id();$t->foreignId('journal_entry_id')->constrained()->restrictOnDelete();$t->foreignId('account_id')->constrained()->restrictOnDelete();$t->decimal('debit',16,2)->default(0);$t->decimal('credit',16,2)->default(0);$t->timestamps();});
  Schema::create('supplier_payments',function(Blueprint $t){$t->id();$t->foreignId('purchase_id')->constrained()->restrictOnDelete();$t->foreignId('supplier_id')->constrained()->restrictOnDelete();$t->foreignId('store_id')->constrained();$t->decimal('amount',14,2);$t->string('method',20);$t->string('reference_no',100)->nullable();$t->foreignId('created_by')->constrained('users');$t->timestamps();});
  foreach ([['1000','Cash','asset'],['1010','Bank / UPI','asset'],['1200','Inventory (demo)','asset'],['1300','Customer Receivables','asset'],['2000','Supplier Payables','liability'],['2100','Output GST (demo)','liability'],['2200','Input GST (demo)','asset'],['4000','Sales Revenue','income'],['5000','Purchases (demo)','expense']] as [$code,$name,$type]) DB::table('accounts')->insert(['store_id'=>1,'code'=>$code,'name'=>$name,'type'=>$type,'created_at'=>now(),'updated_at'=>now()]);
 }
 public function down():void {foreach(['supplier_payments','journal_lines','journal_entries','accounts'] as $table) Schema::dropIfExists($table);}
};