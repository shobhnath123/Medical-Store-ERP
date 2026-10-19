<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TaxCode extends Model {
 use \App\Models\Concerns\BelongsToPharmacyCompany; protected $table = 'tax_codes'; protected $guarded = []; }
