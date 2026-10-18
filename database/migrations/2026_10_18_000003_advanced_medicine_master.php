<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  foreach (['medicine_categories','manufacturers','brands','units'] as $name) {
   Schema::create($name, function (Blueprint $table) { $table->id(); $table->string('name')->unique(); $table->boolean('is_active')->default(true); $table->timestamps(); });
  }
  Schema::create('tax_codes', function (Blueprint $t) { $t->id(); $t->string('name')->unique(); $t->string('hsn_code',16)->nullable(); $t->decimal('illustrative_rate',5,2)->nullable(); $t->boolean('is_active')->default(true); $t->timestamps(); });
  Schema::table('medicines', function (Blueprint $t) {
   $t->foreignId('category_id')->nullable()->constrained('medicine_categories')->nullOnDelete();
   $t->foreignId('manufacturer_id')->nullable()->constrained('manufacturers')->nullOnDelete();
   $t->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
   $t->foreignId('tax_code_id')->nullable()->constrained('tax_codes')->nullOnDelete();
   $t->string('hsn_code',16)->nullable();
   $t->string('composition')->nullable();
   $t->string('dosage_form',60)->nullable();
   $t->string('schedule_type',20)->nullable();
   $t->boolean('prescription_required')->default(false);
  });
  Schema::create('medicine_packings', function(Blueprint $t) {
   $t->id(); $t->foreignId('medicine_id')->constrained()->cascadeOnDelete();
   $t->foreignId('unit_id')->constrained('units')->restrictOnDelete();
   $t->string('label',80); $t->unsignedInteger('units_per_pack');
   $t->string('barcode',100)->nullable()->unique(); $t->boolean('is_active')->default(true);
   $t->timestamps(); $t->unique(['medicine_id','label']);
  });
 }
 public function down(): void {
  Schema::dropIfExists('medicine_packings');
  Schema::table('medicines', function (Blueprint $t) {
   foreach (['category_id','manufacturer_id','brand_id','tax_code_id'] as $col) $t->dropConstrainedForeignId($col);
   $t->dropColumn(['hsn_code','composition','dosage_form','schedule_type','prescription_required']);
  });
  foreach (['tax_codes','units','brands','manufacturers','medicine_categories'] as $name) Schema::dropIfExists($name);
 }
};
