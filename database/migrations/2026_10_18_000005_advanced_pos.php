<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('sales',function(Blueprint $t){
   $t->string('invoice_no',40)->nullable()->unique();
   $t->decimal('subtotal',14,2)->default(0);
   $t->decimal('discount_total',14,2)->default(0);
   $t->string('status',20)->default('posted');
   $t->string('payment_reference',100)->nullable();
  });
  Schema::table('sale_items',function(Blueprint $t){
   $t->decimal('discount_amount',14,2)->default(0);
  });
 }
 public function down(): void {
  Schema::table('sale_items',fn(Blueprint $t)=>$t->dropColumn('discount_amount'));
  Schema::table('sales',fn(Blueprint $t)=>$t->dropColumn(['invoice_no','subtotal','discount_total','status','payment_reference']));
 }
};
