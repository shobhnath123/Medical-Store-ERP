<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MedicineBatch extends Model {
 protected $guarded = [];
 public function medicine(){return $this->belongsTo(Medicine::class);}
 protected function casts(): array { return ['expiry_date'=>'date']; }
}
