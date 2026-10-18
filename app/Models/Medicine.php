<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Medicine extends Model {
 protected $guarded = [];
 protected $casts = ['is_active'=>'boolean','prescription_required'=>'boolean'];
 public function category(){return $this->belongsTo(MedicineCategory::class,'category_id');}
 public function manufacturer(){return $this->belongsTo(Manufacturer::class);}
 public function brand(){return $this->belongsTo(Brand::class);}
 public function taxCode(){return $this->belongsTo(TaxCode::class);}
 public function packings(){return $this->hasMany(MedicinePacking::class);}
}
