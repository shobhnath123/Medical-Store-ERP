<?php
namespace App\Models\Concerns;
use App\Support\StoreContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
trait BelongsToPharmacyCompany {
 protected static function bootBelongsToPharmacyCompany(): void {
  static::addGlobalScope('pharmacy_company',function(Builder $query){
   // Fail closed: no store context means no model reads (including queues/CLI).
   $query->where($query->getModel()->qualifyColumn('company_id'),StoreContext::companyId());
  });
  static::creating(function(Model $model){
   // Never allow a client-supplied company_id to change tenant ownership.
   $model->company_id=StoreContext::companyId();
  });
 }
}
