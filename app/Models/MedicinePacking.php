<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MedicinePacking extends Model {
 use \App\Models\Concerns\BelongsToPharmacyCompany;
 protected $guarded = [];
 public function medicine(){ return $this->belongsTo(Medicine::class); }
 public function unit(){ return $this->belongsTo(Unit::class); }
}
