<?php
use App\Http\Controllers\PharmacyController;
use App\Http\Controllers\ReturnsController;
use App\Http\Controllers\SalesPosController;
use App\Http\Controllers\Admin\MedicineMasterController;
use App\Http\Controllers\Auth\PharmacyLoginController;
use App\Http\Controllers\Admin\PharmacyUserController;
use Illuminate\Support\Facades\Route;
Route::middleware('guest')->group(function(){
 Route::get('/login',[PharmacyLoginController::class,'show'])->name('login');
 Route::post('/login',[PharmacyLoginController::class,'login'])->middleware('throttle:5,1')->name('login.submit');
});
Route::middleware('auth')->group(function(){
 Route::post('/logout',[PharmacyLoginController::class,'logout'])->name('logout');
 Route::get('/',[PharmacyController::class,'home'])->name('dashboard');
 Route::get('/medicines',[MedicineMasterController::class,'index'])->name('medicines.index');
 Route::get('/api/medicine-search',[MedicineMasterController::class,'search'])->middleware('throttle:60,1')->name('medicines.search');
 Route::get('/suppliers',[PharmacyController::class,'suppliers'])->name('suppliers.index');
 Route::get('/purchases',[PharmacyController::class,'purchases'])->name('purchases.index');
 Route::get('/inventory',[PharmacyController::class,'inventory'])->name('inventory.index');
 Route::get('/sales',[SalesPosController::class,'index'])->name('sales.index');
 Route::get('/sales/{sale}',[SalesPosController::class,'show'])->name('sales.show');
 Route::middleware('can:manage-pharmacy')->group(function(){
  Route::post('/medicines',[MedicineMasterController::class,'store'])->name('medicines.store');
  Route::put('/medicines/{medicine}',[MedicineMasterController::class,'update'])->name('medicines.update');
  Route::post('/medicines/{medicine}/packings',[MedicineMasterController::class,'addPacking'])->name('medicines.packings.store');
  Route::get('/masters/{type}',[MedicineMasterController::class,'masters'])->name('masters.index');
  Route::post('/masters/{type}',[MedicineMasterController::class,'storeMaster'])->name('masters.store');
  Route::put('/masters/{type}/{id}',[MedicineMasterController::class,'updateMaster'])->name('masters.update');
  Route::post('/suppliers',[PharmacyController::class,'supplierStore'])->name('suppliers.store');
  Route::post('/purchases',[PharmacyController::class,'purchaseStore'])->name('purchases.store');
 });
 Route::middleware('can:bill-pharmacy')->post('/sales',[SalesPosController::class,'store'])->name('sales.store');
 Route::middleware('can:manage-pharmacy')->group(function(){
  Route::get('/returns',[ReturnsController::class,'index'])->name('returns.index');
  Route::post('/returns/sales',[ReturnsController::class,'sale'])->name('returns.sale');
  Route::post('/returns/purchases',[ReturnsController::class,'purchase'])->name('returns.purchase');
  Route::post('/returns/adjustments',[ReturnsController::class,'adjustment'])->name('returns.adjustment');
 });
 Route::middleware('can:manage-users')->group(function(){
  Route::get('/admin/users',[PharmacyUserController::class,'index'])->name('admin.users.index');
  Route::post('/admin/users',[PharmacyUserController::class,'store'])->name('admin.users.store');
 });
});
