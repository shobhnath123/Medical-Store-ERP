<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 private const TABLES=['medicines','medicine_categories','manufacturers','brands','units','tax_codes','medicine_packings','medicine_batches','suppliers'];
 public function up(): void {
  foreach(self::TABLES as $table){
   Schema::table($table,function(Blueprint $t) {
    $t->foreignId('company_id')->default(1)->constrained('companies')->restrictOnDelete();
    $t->index('company_id');
   });
  }
 }
 public function down(): void {
  foreach(array_reverse(self::TABLES) as $table){
   Schema::table($table,function(Blueprint $t) {$t->dropIndex(['company_id']);$t->dropConstrainedForeignId('company_id');});
  }
 }
};
