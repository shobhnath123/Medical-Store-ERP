@extends('layouts.app')
@section('content')
<h1>{{ ucwords(str_replace('-', ' ', $type)) }}</h1>
<a href="{{ route('medicines.index') }}">← Medicine Master</a>
<div class="card"><h2>Add {{ str_replace('-', ' ', $type) }}</h2>
<form method="post" action="{{ route('masters.store',$type) }}">@csrf
<div class="grid"><div><label>Name</label><input name="name" required maxlength="255" value="{{ old('name') }}"></div>
@if($type==='tax-codes')<div><label>HSN (optional)</label><input name="hsn_code" maxlength="16"></div><div><label>Reference rate % (not used in billing)</label><input name="illustrative_rate" type="number" step="0.01" min="0" max="100"></div>@endif</div>
<button>Add</button></form></div>
<div class="card"><table><thead><tr><th>ID</th><th>Name</th><th>Status</th><th>Update</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td>{{ $row->id }}</td><td>{{ $row->name }}</td><td>{{ $row->is_active?'Active':'Inactive' }}</td><td>
<form method="post" action="{{ route('masters.update',[$type,$row->id]) }}">@csrf @method('PUT')<input name="name" required value="{{ $row->name }}"><select name="is_active"><option value="1" @selected($row->is_active)>Active</option><option value="0" @selected(!$row->is_active)>Inactive</option></select><button>Save</button></form></td></tr>
@empty<tr><td colspan="4">No records yet.</td></tr>@endforelse</tbody></table>{{ $rows->links() }}</div>
@endsection
