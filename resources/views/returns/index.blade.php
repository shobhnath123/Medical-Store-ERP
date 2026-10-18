@extends('layouts.app')
@section('content')
<h1>Returns & Stock Adjustments (Demo)</h1><p><strong>Safety:</strong> Customer returns are quarantined, not credited to sellable stock. Refunds and supplier credits are recorded as pending, not paid.</p>
<div class="grid">
<section class="card"><h2>Customer Sales Return</h2><form method="post" action="{{ route('returns.sale') }}">@csrf
<label>Original sale</label><select name="sale_id" required><option value="">Select sale</option>@foreach($sales as $s)<option value="{{$s->id}}">{{$s->invoice_no ?: $s->id}} · ₹{{$s->total}}</option>@endforeach</select>
<label>Sale item ID (from matching sale)</label><select name="sale_item_id" required><option value="">Select item</option>@foreach($saleItems as $i)<option value="{{$i->id}}">Item #{{$i->id}} — Sale #{{$i->sale_id}} ({{$i->quantity}} units)</option>@endforeach</select>
<label>Returned quantity (base units)</label><input name="quantity" type="number" min="1" required>
<label>Reason</label><input name="reason" required maxlength="255"><button>Post Quarantined Return</button></form></section>
<section class="card"><h2>Supplier Purchase Return</h2><form method="post" action="{{ route('returns.purchase') }}">@csrf
<label>Original purchase</label><select name="purchase_id" required><option value="">Select purchase</option>@foreach($purchases as $p)<option value="{{$p->id}}">#{{$p->id}} — {{$p->invoice_no}}</option>@endforeach</select>
<label>Purchase item</label><select name="purchase_item_id" required><option value="">Select item</option>@foreach($purchaseItems as $i)<option value="{{$i->id}}">Item #{{$i->id}} — Purchase #{{$i->purchase_id}} ({{$i->quantity}} units)</option>@endforeach</select>
<label>Return quantity (base units)</label><input name="quantity" type="number" min="1" required><label>Reason</label><input name="reason" required maxlength="255"><button>Post Supplier Return</button></form></section>
<section class="card"><h2>Stock Adjustment (Manager)</h2><form method="post" action="{{ route('returns.adjustment') }}">@csrf
<label>Batch</label><select name="medicine_batch_id" required><option value="">Select batch</option>@foreach($batches as $b)<option value="{{$b->id}}">{{$b->medicine?->name}} / {{$b->batch_no}} (#{{$b->id}})</option>@endforeach</select>
<label>Quantity change (+ or - base units)</label><input type="number" name="quantity_change" required min="-1000000" max="1000000"><label>Reason</label><input name="reason" required maxlength="255"><button>Post Adjustment</button></form></section>
</div>
<div class="card"><h2>Latest Sales Returns</h2><table><tr><th>ID</th><th>Sale</th><th>Refund Due</th><th>Status</th></tr>@foreach($salesReturns as $r)<tr><td>{{$r->id}}</td><td>{{$r->sale_id}}</td><td>₹{{$r->refund_due}}</td><td>{{$r->refund_status}}</td></tr>@endforeach</table></div>
<div class="card"><h2>Latest Supplier Returns</h2><table><tr><th>ID</th><th>Purchase</th><th>Credit Due</th><th>Status</th></tr>@foreach($purchaseReturns as $r)<tr><td>{{$r->id}}</td><td>{{$r->purchase_id}}</td><td>₹{{$r->supplier_credit_due}}</td><td>{{$r->settlement_status}}</td></tr>@endforeach</table></div>
<div class="card"><h2>Latest Stock Adjustments</h2><table><tr><th>ID</th><th>Batch</th><th>Change</th><th>Reason</th></tr>@foreach($adjustments as $a)<tr><td>{{$a->id}}</td><td>{{$a->medicine_batch_id}}</td><td>{{$a->quantity_change}}</td><td>{{$a->reason}}</td></tr>@endforeach</table></div>
@endsection