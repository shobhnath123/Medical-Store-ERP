<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MedicineBatch extends Model {
 use \App\Models\Concerns\BelongsToPharmacyCompany;
 protected $guarded = [];
 public function medicine(){return $this->belongsTo(Medicine::class);}
 protected function casts(): array { return ['expiry_date'=>'date']; }
}
