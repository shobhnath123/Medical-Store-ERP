<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Manufacturer extends Model {
 use \App\Models\Concerns\BelongsToPharmacyCompany; protected $table = 'manufacturers'; protected $guarded = []; }
