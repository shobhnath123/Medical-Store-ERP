<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('sales_returns',function(Blueprint $t){$t->id();$t->foreignId('sale_id')->constrained('sales')->restrictOnDelete();$t->foreignId('store_id')->constrained();$t->foreignId('created_by')->constrained('users');$t->string('reason',255);$t->decimal('refund_due',14,2)->default(0);$t->string('refund_status',25)->default('pending');$t->string('status',20)->default('posted');$t->timestamps();});
  Schema::create('sales_return_items',function(Blueprint $t){$t->id();$t->foreignId('sales_return_id')->constrained()->restrictOnDelete();$t->foreignId('sale_item_id')->constrained()->restrictOnDelete();$t->unsignedBigInteger('quantity');$t->decimal('refund_amount',14,2);$t->string('disposition',25)->default('quarantine');$t->timestamps();});
  Schema::create('purchase_returns',function(Blueprint $t){$t->id();$t->foreignId('purchase_id')->constrained()->restrictOnDelete();$t->foreignId('store_id')->constrained();$t->foreignId('created_by')->constrained('users');$t->string('reason',255);$t->decimal('supplier_credit_due',14,2)->default(0);$t->string('settlement_status',25)->default('pending');$t->timestamps();});
  Schema::create('purchase_return_items',function(Blueprint $t){$t->id();$t->foreignId('purchase_return_id')->constrained()->restrictOnDelete();$t->foreignId('purchase_item_id')->constrained()->restrictOnDelete();$t->unsignedBigInteger('quantity');$t->decimal('credit_amount',14,2);$t->timestamps();});
  Schema::create('stock_adjustments',function(Blueprint $t){$t->id();$t->foreignId('store_id')->constrained();$t->foreignId('medicine_batch_id')->constrained();$t->foreignId('created_by')->constrained('users');$t->bigInteger('quantity_change');$t->string('reason',255);$t->timestamps();});
 }
 public function down(): void {foreach(['stock_adjustments','purchase_return_items','purchase_returns','sales_return_items','sales_returns'] as $table) Schema::dropIfExists($table);}
};