@extends('layouts.app')
@section('content')
<h1>Advanced Medicine Master</h1>
@can('manage-pharmacy')<div class="card"><strong>Setup masters:</strong>
@foreach(['categories'=>'Categories','manufacturers'=>'Manufacturers','brands'=>'Brands','units'=>'Units','tax-codes'=>'Tax Codes'] as $key=>$label)
<a style="margin-right:15px" href="{{route('masters.index',$key)}}">{{$label}}</a>
@endforeach</div>
<div class="card"><h2>Add medicine</h2><form method="post" action="{{route('medicines.store')}}">@csrf
<div class="grid">
<div><label>Medicine name</label><input required name="name" value="{{old('name')}}"></div>
<div><label>Generic name</label><input name="generic_name" value="{{old('generic_name')}}"></div>
<div><label>Composition</label><input name="composition" value="{{old('composition')}}"></div>
<div><label>Dosage form</label><input name="dosage_form" placeholder="Tablet / Syrup / Capsule" value="{{old('dosage_form')}}"></div>
<div><label>Category</label><select name="category_id"><option value="">Select</option>@foreach($categories as $x)<option value="{{$x->id}}">{{$x->name}}</option>@endforeach</select></div>
<div><label>Manufacturer</label><select name="manufacturer_id"><option value="">Select</option>@foreach($manufacturers as $x)<option value="{{$x->id}}">{{$x->name}}</option>@endforeach</select></div>
<div><label>Brand</label><select name="brand_id"><option value="">Select</option>@foreach($brands as $x)<option value="{{$x->id}}">{{$x->name}}</option>@endforeach</select></div>
<div><label>Tax code (reference only)</label><select name="tax_code_id"><option value="">Select</option>@foreach($taxCodes as $x)<option value="{{$x->id}}">{{$x->name}}</option>@endforeach</select></div>
<div><label>HSN</label><input name="hsn_code" maxlength="16"></div>
<div><label>Units per strip (legacy conversion)</label><input required type="number" name="units_per_strip" min="1" value="{{old('units_per_strip',10)}}"></div>
<div><label>Schedule</label><select name="schedule_type"><option value="">Not set</option>@foreach(['H','H1','X','OTHER'] as $schedule)<option>{{$schedule}}</option>@endforeach</select></div>
<div><label>Prescription required?</label><select name="prescription_required"><option value="0">No</option><option value="1">Yes</option></select></div>
<div><label>Status</label><select name="is_active"><option value="1">Active</option><option value="0">Inactive</option></select></div>
</div><button>Save medicine</button></form></div>@endcan
<div class="card"><form method="get" action="{{route('medicines.index')}}"><label>Search name, generic or exact barcode</label><input name="search" value="{{request('search')}}" placeholder="Search medicines"><button>Search</button></form></div>
@foreach($rows as $m)
<div class="card"><h3>#{{$m->id}} {{$m->name}} <small>({{$m->generic_name}})</small></h3>
<p>Category: {{$m->category?->name ?? '—'}} | Manufacturer: {{$m->manufacturer?->name ?? '—'}} | Brand: {{$m->brand?->name ?? '—'}} | Status: {{$m->is_active?'Active':'Inactive'}}</p>
<p>Packings: @forelse($m->packings as $p)<span>{{$p->label}} ({{$p->units_per_pack}} units{{ $p->barcode?', barcode '.$p->barcode:'' }}) · </span>@empty None @endforelse</p>
@can('manage-pharmacy')<details><summary>Edit medicine</summary><form method="post" action="{{route('medicines.update',$m)}}">@csrf @method('PUT')<div class="grid">
<div><label>Name</label><input name="name" required value="{{$m->name}}"></div><div><label>Generic</label><input name="generic_name" value="{{$m->generic_name}}"></div><div><label>Composition</label><input name="composition" value="{{$m->composition}}"></div>
<div><label>Dosage form</label><input name="dosage_form" value="{{$m->dosage_form}}"></div><div><label>HSN</label><input name="hsn_code" value="{{$m->hsn_code}}"></div>
<div><label>Units/strip</label><input type="number" min="1" name="units_per_strip" value="{{$m->units_per_strip}}"></div>
@foreach(['category_id'=>$categories,'manufacturer_id'=>$manufacturers,'brand_id'=>$brands,'tax_code_id'=>$taxCodes] as $field=>$options)<div><label>{{str_replace('_',' ',$field)}}</label><select name="{{$field}}"><option value="">None</option>@foreach($options as $x)<option value="{{$x->id}}" @selected($m->$field==$x->id)>{{$x->name}}</option>@endforeach</select></div>@endforeach
<div><label>Schedule</label><select name="schedule_type"><option value="">None</option>@foreach(['H','H1','X','OTHER'] as $schedule)<option value="{{$schedule}}" @selected($m->schedule_type===$schedule)>{{$schedule}}</option>@endforeach</select></div>
<div><label>Prescription required</label><select name="prescription_required"><option value="0" @selected(!$m->prescription_required)>No</option><option value="1" @selected($m->prescription_required)>Yes</option></select></div>
<div><label>Status</label><select name="is_active"><option value="1" @selected($m->is_active)>Active</option><option value="0" @selected(!$m->is_active)>Inactive</option></select></div>
</div><button>Update medicine</button></form></details>
<details><summary>Add packing / barcode</summary><form method="post" action="{{route('medicines.packings.store',$m)}}">@csrf<div class="grid"><div><label>Packing label</label><input required name="label" placeholder="Strip of 10"></div><div><label>Unit</label><select required name="unit_id">@foreach($units as $x)<option value="{{$x->id}}">{{$x->name}}</option>@endforeach</select></div><div><label>Smallest units per pack</label><input required type="number" name="units_per_pack" min="1"></div><div><label>Barcode (optional)</label><input name="barcode"></div></div><button>Add packing</button></form></details>@endcan
</div>
@endforeach
<div>{{ $rows->links() }}</div>
@endsection
