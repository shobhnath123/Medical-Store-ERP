@extends('layouts.app')
@section('content')
<h1>Pharmacy Reports & Analytics (V9 Demo)</h1>
<p><strong>Important:</strong> Single-store demo. Unreconciled returns / older journals affect these reports. This is not an audited P&amp;L, tax return or statutory valuation.</p>
<form method="GET" action="{{ route('reports.index') }}" class="card" style="display:flex;flex-wrap:wrap;gap:12px;align-items:end">
 <label>From <input type="date" name="from" value="{{ $from }}" required></label>
 <label>To <input type="date" name="to" value="{{ $to }}" required></label>
 <label>Expiry horizon <select name="expiry_days">@foreach([30,60,90,180] as $d)<option value="{{ $d }}" @selected($days === $d)>{{ $d }} days</option>@endforeach</select></label>
 <button type="submit">Apply Filters</button><button type="button" onclick="window.print()">Print / Save as PDF</button>
</form>
@php($qs = ['from'=>$from,'to'=>$to,'expiry_days'=>$days])
<div class="grid">
 <div class="card"><h3>Gross Sales in Period</h3><h2>₹{{ number_format($sales->sum('gross_sales'),2) }}</h2><small>{{ $sales->sum('invoices') }} invoices, excludes sales returns</small></div>
 <div class="card"><h3>Supplier Outstanding (Estimate)</h3><h2>₹{{ number_format($purchases->sum('outstanding'),2) }}</h2><small>Does not deduct purchase return credits</small></div>
 <div class="card"><h3>Stock Units</h3><h2>{{ number_format($stock->sum('quantity')) }}</h2><small>Includes expired stock still in balance</small></div>
 <div class="card"><h3>Estimated Stock Value</h3><h2>₹{{ number_format($stock->sum('estimated_value'),2) }}</h2><small>Last received cost; missing costs excluded</small></div>
</div>
<div class="card"><h3>Reconciliation Warning</h3><p>Posted sales missing journal: <strong>{{ $unpostedSales }}</strong> · Purchases missing journal: <strong>{{ $unpostedPurchases }}</strong>. These records require reconciliation before financial statements.</p></div>
@php($makeTable = true)
<div class="card"><h2>Daily Sales</h2><p><a href="{{ route('reports.export',['type'=>'sales']+$qs) }}">Export CSV</a></p><div style="overflow:auto"><table><thead><tr><th>Date</th><th>Invoices</th><th>Gross Sales</th><th>Tax Snapshot</th></tr></thead><tbody>@forelse($sales as $row)<tr><td>{{ $row->day }}</td><td>{{ $row->invoices }}</td><td>₹{{ number_format($row->gross_sales,2) }}</td><td>₹{{ number_format($row->tax_total,2) }}</td></tr>@empty<tr><td colspan="4">No sales in selected period.</td></tr>@endforelse</tbody></table></div></div>
<div class="card"><h2>Supplier Outstanding</h2><p><a href="{{ route('reports.export',['type'=>'outstanding']+$qs) }}">Export CSV</a></p><div style="overflow:auto"><table><thead><tr><th>Supplier</th><th>Invoice</th><th>Due</th><th>Paid</th><th>Remaining</th></tr></thead><tbody>@forelse($purchases as $row)<tr><td>{{ $row->supplier }}</td><td>{{ $row->invoice_no }}</td><td>₹{{ number_format($row->total,2) }}</td><td>₹{{ number_format($row->amount_paid,2) }}</td><td>₹{{ number_format($row->outstanding,2) }}</td></tr>@empty<tr><td colspan="5">No purchases.</td></tr>@endforelse</tbody></table></div></div>
<div class="card"><h2>Batch Stock & Approximate Value</h2><p><a href="{{ route('reports.export',['type'=>'stock']+$qs) }}">Export CSV</a></p><div style="overflow:auto"><table><thead><tr><th>Medicine</th><th>Batch</th><th>Expiry</th><th>Units</th><th>Last Cost/Unit</th><th>Estimated Value</th></tr></thead><tbody>@forelse($stock as $row)<tr><td>{{ $row->medicine }}</td><td>{{ $row->batch_no }}</td><td>{{ $row->expiry_date }}</td><td>{{ $row->quantity }}</td><td>{{ $row->last_purchase_cost === null ? 'N/A' : number_format($row->last_purchase_cost,2) }}</td><td>{{ $row->estimated_value === null ? 'N/A' : number_format($row->estimated_value,2) }}</td></tr>@empty<tr><td colspan="6">No stock.</td></tr>@endforelse</tbody></table></div></div>
<div class="card"><h2>Expired / Near-Expiry ({{ $days }} days)</h2><p><a href="{{ route('reports.export',['type'=>'expiry']+$qs) }}">Export CSV</a></p><div style="overflow:auto"><table><thead><tr><th>Medicine</th><th>Batch</th><th>Expiry</th><th>Units</th></tr></thead><tbody>@forelse($expiring as $row)<tr><td>{{ $row->medicine }}</td><td>{{ $row->batch_no }}</td><td>{{ $row->expiry_date }}</td><td>{{ $row->quantity }}</td></tr>@empty<tr><td colspan="4">No expiring stock found.</td></tr>@endforelse</tbody></table></div></div>
<div class="card"><h2>Trial Balance (Journal Totals, Not Closing Balance)</h2><p><a href="{{ route('reports.export',['type'=>'trial-balance']+$qs) }}">Export CSV</a></p><div style="overflow:auto"><table><thead><tr><th>Code</th><th>Account</th><th>Debit</th><th>Credit</th></tr></thead><tbody>@forelse($financials as $row)<tr><td>{{ $row->code }}</td><td>{{ $row->name }}</td><td>₹{{ number_format($row->debit,2) }}</td><td>₹{{ number_format($row->credit,2) }}</td></tr>@empty<tr><td colspan="4">No posted journals.</td></tr>@endforelse</tbody></table></div></div>
@endsection
