<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Models\Medicine;
use App\Models\User;
class CompanyIsolationTest extends TestCase {
 use RefreshDatabase;
 public function test_store_context_denies_unassigned_store(): void {
  $this->markTestIncomplete('Provide company/store/user fixtures in your app test factory; assert 403 on foreign store selection.');
 }
 public function test_medicine_scope_hides_another_company(): void {
  $this->markTestIncomplete('Seed company A/B, authenticate A, select store A, assert medicine B returns 404 and is excluded from search.');
 }
 public function test_purchase_rejects_foreign_supplier_id(): void {
  $this->markTestIncomplete('Seed company A/B suppliers and assert POST purchase rejects B supplier; extend service validations.');
 }
}
