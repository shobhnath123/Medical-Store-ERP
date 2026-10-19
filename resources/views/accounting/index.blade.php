@extends('layouts.app')
@section('content')
<h1>Accounting & Financial Reports (V8 Demo)</h1>
<div class="card"><h2>Important</h2><p>Only NEW V8 sales/purchases generate journals. Prior transactions require a reviewed backfill. No customer credit sales, GST reconciliation, stock valuation/COGS, opening balances, or full P&L yet.</p><p><strong>Unposted historic sales:</strong> {{ $unpostedSales }} · <strong>Unposted historic purchases:</strong> {{ $unpostedPurchases }}</p></div>
<div class="card"><h2>Supplier Payment</h2><form method="POST" action="{{ route('accounting.supplier-payments.store') }}">@csrf
<label>Purchase</label><select name="purchase_id" required><option value="">Select posted purchase</option>@foreach($purchases as $purchase)<option value="{{ $purchase->id }}">#{{ $purchase->id }} / {{ $purchase->invoice_no }} · ₹{{ number_format($purchase->total,2) }} · Paid ₹{{ number_format($payments[$purchase->id] ?? 0,2) }}</option>@endforeach</select>
<label>Amount (INR)</label><input type="number" name="amount" min="0.01" step="0.01" required>
<label>Method</label><select name="method"><option value="cash">Cash</option><option value="bank">Bank/UPI</option></select>
<label>Reference (optional)</label><input name="reference_no" maxlength="100">
<button>Post Supplier Payment</button></form></div>
<div class="card"><h2>Chart of Accounts</h2><table><thead><tr><th>Code</th><th>Account</th><th>Type</th></tr></thead><tbody>@foreach($accounts as $a)<tr><td>{{ $a->code }}</td><td>{{ $a->name }}</td><td>{{ $a->type }}</td></tr>@endforeach</tbody></table></div>
<div class="card"><h2>Trial Balance (posted V8 journals only)</h2><table><thead><tr><th>Code</th><th>Account</th><th>Debits</th><th>Credits</th></tr></thead><tbody>@foreach($trial as $line)<tr><td>{{ $line->code }}</td><td>{{ $line->name }}</td><td>₹{{number_format($line->debit,2)}}</td><td>₹{{number_format($line->credit,2)}}</td></tr>@endforeach<tr><th colspan="2">Total</th><th>₹{{number_format($trial->sum('debit'),2)}}</th><th>₹{{number_format($trial->sum('credit'),2)}}</th></tr></tbody></table></div>
<div class="card"><h2>Latest Journal Entries</h2><table><thead><tr><th>Date</th><th>Reference</th><th>Description</th></tr></thead><tbody>@foreach($entries as $entry)<tr><td>{{ $entry->entry_date }}</td><td>{{ $entry->reference_type }} #{{ $entry->reference_id }}</td><td>{{ $entry->description }}</td></tr>@endforeach</tbody></table></div>
@endsection