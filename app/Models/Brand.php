<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Brand extends Model {
 use \App\Models\Concerns\BelongsToPharmacyCompany; protected $table = 'brands'; protected $guarded = []; }
