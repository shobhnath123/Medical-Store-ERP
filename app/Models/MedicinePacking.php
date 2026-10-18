<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MedicinePacking extends Model {
 protected $guarded = [];
 public function medicine(){ return $this->belongsTo(Medicine::class); }
 public function unit(){ return $this->belongsTo(Unit::class); }
}
