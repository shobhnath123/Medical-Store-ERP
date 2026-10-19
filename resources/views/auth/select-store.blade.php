@extends('layouts.app')
@section('content')
<div class="card"><h1>Select your pharmacy branch</h1><p>Only branches assigned to your account are available.</p>
@if($stores->isEmpty())<p class="err">No active store access. Ask your administrator to assign a branch.</p>@else
<form method="POST" action="{{ route('store.select.submit') }}">@csrf
<label for="store_id">Branch</label><select id="store_id" name="store_id" required>
@foreach($stores as $store)<option value="{{ $store->id }}">{{ $store->name }} ({{ $store->code }})</option>@endforeach
</select><button type="submit">Continue</button></form>@endif</div>
@endsection
