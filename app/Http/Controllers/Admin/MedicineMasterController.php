<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{Medicine,MedicineCategory,Manufacturer,Brand,Unit,TaxCode,MedicinePacking};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class MedicineMasterController extends Controller {
 private const MASTERS = ['categories'=>MedicineCategory::class,'manufacturers'=>Manufacturer::class,'brands'=>Brand::class,'units'=>Unit::class,'tax-codes'=>TaxCode::class];
 private function masterClass(string $type): string {abort_unless(isset(self::MASTERS[$type]),404); return self::MASTERS[$type];}
 public function masters(string $type){$class=$this->masterClass($type);return view('medicine-master.masters',['type'=>$type,'rows'=>$class::orderBy('name')->paginate(25)]);}
 public function storeMaster(Request $request,string $type){$class=$this->masterClass($type);
  $data=$request->validate(['name'=>['required','string','max:255',Rule::unique((new $class)->getTable(),'name')], 'hsn_code'=>['nullable','string','max:16'], 'illustrative_rate'=>['nullable','numeric','between:0,100']]);
  if($type!=='tax-codes') unset($data['hsn_code'],$data['illustrative_rate']);
  $class::create($data);return back()->with('success','Master record created');
 }
 public function updateMaster(Request $request,string $type,int $id){$class=$this->masterClass($type);$item=$class::findOrFail($id);
  $data=$request->validate(['name'=>['required','string','max:255',Rule::unique($item->getTable(),'name')->ignore($id)],'is_active'=>['required','boolean']]);
  $item->update($data);return back()->with('success','Master record updated');
 }
 public function index(Request $request){
  $q=Medicine::with(['category','manufacturer','brand','packings']);
  if($request->filled('search')){$s=trim($request->input('search'));$q->where(function($query) use($s){$query->where('name','like',"%{$s}%")->orWhere('generic_name','like',"%{$s}%")->orWhereHas('packings',fn($p)=>$p->where('barcode',$s));});}
  return view('medicine-master.index',['rows'=>$q->orderBy('name')->paginate(20)->withQueryString(),'categories'=>MedicineCategory::where('is_active',true)->orderBy('name')->get(),'manufacturers'=>Manufacturer::where('is_active',true)->orderBy('name')->get(),'brands'=>Brand::where('is_active',true)->orderBy('name')->get(),'taxCodes'=>TaxCode::where('is_active',true)->orderBy('name')->get(),'units'=>Unit::where('is_active',true)->orderBy('name')->get()]);
 }
 private function validateMedicine(Request $request,?Medicine $medicine=null): array {return $request->validate([
  'name'=>['required','string','max:255'],'generic_name'=>['nullable','string','max:255'],
  'units_per_strip'=>['required','integer','min:1','max:10000'],
  'category_id'=>['nullable','exists:medicine_categories,id'],'manufacturer_id'=>['nullable','exists:manufacturers,id'],
  'brand_id'=>['nullable','exists:brands,id'],'tax_code_id'=>['nullable','exists:tax_codes,id'],
  'hsn_code'=>['nullable','string','max:16'],'composition'=>['nullable','string','max:255'],
  'dosage_form'=>['nullable','string','max:60'],'schedule_type'=>['nullable',Rule::in(['H','H1','X','OTHER'])],
  'prescription_required'=>['required','boolean'],'is_active'=>['required','boolean']
 ]);}
 public function store(Request $request){$data=$this->validateMedicine($request);Medicine::create($data);return back()->with('success','Medicine added');}
 public function update(Request $request,Medicine $medicine){$medicine->update($this->validateMedicine($request,$medicine));return back()->with('success','Medicine updated');}
 public function addPacking(Request $request,Medicine $medicine){$data=$request->validate([
  'unit_id'=>['required','exists:units,id'],'label'=>['required','string','max:80',Rule::unique('medicine_packings','label')->where('medicine_id',$medicine->id)],
  'units_per_pack'=>['required','integer','min:1','max:100000'],
  'barcode'=>['nullable','string','max:100',Rule::unique('medicine_packings','barcode')]
 ]);$medicine->packings()->create($data);return back()->with('success','Packing added');}
 public function search(Request $request){$search=trim((string)$request->query('q',''));
  if(strlen($search)<2)return response()->json([]);
  $rows=Medicine::query()->where('is_active',true)->where(function($q)use($search){$q->where('name','like',"%{$search}%")->orWhere('generic_name','like',"%{$search}%")->orWhereHas('packings',fn($p)=>$p->where('barcode',$search));})->limit(20)->get(['id','name','generic_name','units_per_strip']);
  return response()->json($rows);
 }
}
