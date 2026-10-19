<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MedicineCategory extends Model {
 use \App\Models\Concerns\BelongsToPharmacyCompany; protected $table = 'medicine_categories'; protected $guarded = []; }
