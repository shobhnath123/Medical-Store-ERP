<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Unit extends Model {
 use \App\Models\Concerns\BelongsToPharmacyCompany; protected $table = 'units'; protected $guarded = []; }
