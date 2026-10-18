<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('stores', function(Blueprint $t){$t->id();$t->string('name');$t->timestamps();});
  DB::table('stores')->insert(['id'=>1,'name'=>'Demo Pharmacy','created_at'=>now(),'updated_at'=>now()]);
  Schema::create('medicines', function(Blueprint $t){$t->id();$t->string('name');$t->string('generic_name')->nullable();$t->unsignedInteger('units_per_strip')->default(10);$t->boolean('is_active')->default(true);$t->timestamps();});
  Schema::create('suppliers', function(Blueprint $t){$t->id();$t->string('name');$t->string('phone')->nullable();$t->string('gstin',15)->nullable();$t->timestamps();});
  Schema::create('medicine_batches', function(Blueprint $t){$t->id();$t->foreignId('medicine_id')->constrained()->restrictOnDelete();$t->string('batch_no',80);$t->date('expiry_date');$t->decimal('mrp_per_unit',14,2);$t->decimal('sale_price_per_unit',14,2);$t->timestamps();$t->unique(['medicine_id','batch_no','expiry_date'],'batch_identity');});
  Schema::create('purchases', function(Blueprint $t){$t->id();$t->foreignId('store_id')->constrained();$t->foreignId('supplier_id')->constrained();$t->string('invoice_no');$t->date('invoice_date');$t->decimal('total',14,2);$t->timestamps();$t->unique(['store_id','supplier_id','invoice_no']);});
  Schema::create('purchase_items', function(Blueprint $t){$t->id();$t->foreignId('purchase_id')->constrained();$t->foreignId('medicine_batch_id')->constrained();$t->unsignedBigInteger('quantity');$t->decimal('cost_per_unit',14,2);$t->decimal('line_total',14,2);$t->timestamps();});
  Schema::create('stock_balances', function(Blueprint $t){$t->id();$t->foreignId('store_id')->constrained();$t->foreignId('medicine_batch_id')->constrained();$t->unsignedBigInteger('quantity')->default(0);$t->timestamps();$t->unique(['store_id','medicine_batch_id']);});
  Schema::create('stock_movements', function(Blueprint $t){$t->id();$t->foreignId('store_id')->constrained();$t->foreignId('medicine_batch_id')->constrained();$t->string('type',20);$t->bigInteger('quantity_change');$t->string('reference_type');$t->unsignedBigInteger('reference_id');$t->timestamps();$t->index(['reference_type','reference_id']);});
  Schema::create('sales', function(Blueprint $t){$t->id();$t->foreignId('store_id')->constrained();$t->string('customer_name')->nullable();$t->decimal('total',14,2);$t->string('payment_method',20);$t->timestamps();});
  Schema::create('sale_items', function(Blueprint $t){$t->id();$t->foreignId('sale_id')->constrained();$t->foreignId('medicine_batch_id')->constrained();$t->unsignedBigInteger('quantity');$t->decimal('unit_price',14,2);$t->decimal('line_total',14,2);$t->timestamps();});
 }
 public function down(): void {foreach(['sale_items','sales','stock_movements','stock_balances','purchase_items','purchases','medicine_batches','suppliers','medicines','stores'] as $name) Schema::dropIfExists($name);}
};
