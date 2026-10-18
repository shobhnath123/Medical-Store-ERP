<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::table('sales',function(Blueprint $t){
   $t->decimal('taxable_total',14,2)->default(0);
   $t->decimal('tax_total',14,2)->default(0);
   $t->string('tax_mode',20)->default('inclusive');
  });
  Schema::table('sale_items',function(Blueprint $t){
   $t->foreignId('medicine_packing_id')->nullable()->constrained('medicine_packings')->nullOnDelete();
   $t->unsignedInteger('pack_quantity')->nullable();
   $t->unsignedInteger('units_per_pack')->default(1);
   $t->decimal('tax_rate',5,2)->default(0);
   $t->decimal('taxable_amount',14,2)->default(0);
   $t->decimal('tax_amount',14,2)->default(0);
   $t->string('hsn_code',16)->nullable();
  });
 }
 public function down():void {
  Schema::table('sale_items',function(Blueprint $t){
   $t->dropConstrainedForeignId('medicine_packing_id');
   $t->dropColumn(['pack_quantity','units_per_pack','tax_rate','taxable_amount','tax_amount','hsn_code']);
  });
  Schema::table('sales',fn(Blueprint $t)=>$t->dropColumn(['taxable_total','tax_total','tax_mode']));
 }
};
