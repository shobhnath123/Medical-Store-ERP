<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('purchases', function(Blueprint $t) {
   $t->decimal('subtotal',14,2)->default(0);
   $t->decimal('discount_total',14,2)->default(0);
   $t->decimal('tax_total',14,2)->default(0);
   $t->string('status',20)->default('posted');
  });
  Schema::table('purchase_items', function(Blueprint $t) {
   $t->foreignId('medicine_packing_id')->nullable()->constrained('medicine_packings')->restrictOnDelete();
   $t->unsignedBigInteger('packs_purchased')->default(0);
   $t->unsignedBigInteger('free_quantity')->default(0);
   $t->decimal('discount_percent',5,2)->default(0);
   $t->decimal('discount_amount',14,2)->default(0);
   $t->decimal('tax_rate',5,2)->default(0);
   $t->decimal('tax_amount',14,2)->default(0);
   $t->decimal('taxable_amount',14,2)->default(0);
  });
 }
 public function down(): void {
  Schema::table('purchase_items',function(Blueprint $t){
   $t->dropConstrainedForeignId('medicine_packing_id');
   $t->dropColumn(['packs_purchased','free_quantity','discount_percent','discount_amount','tax_rate','tax_amount','taxable_amount']);
  });
  Schema::table('purchases',function(Blueprint $t){$t->dropColumn(['subtotal','discount_total','tax_total','status']);});
 }
};
