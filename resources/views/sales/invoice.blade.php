@extends('layouts.app')
@section('content')
<style>@media print{header,.no-print{display:none!important}body{background:white}main{max-width:none;margin:0}.card{box-shadow:none;padding:0}}</style>
<div class="no-print"><a href="{{route('sales.index')}}">Back</a> <button onclick="window.print()">Print demo receipt</button></div>
<div class="card"><h1>DEMO Pharmacy Receipt – NOT GST Invoice</h1><p>Invoice: {{$sale->invoice_no}} | {{$sale->created_at->format('d-m-Y H:i')}} | {{$sale->customer_name ?: 'Walk-in'}}</p>
<table><thead><tr><th>Medicine / Batch</th><th>Pack Qty</th><th>Base Units</th><th>HSN</th><th>Tax %</th><th>Tax incl.</th></tr></thead><tbody>@foreach($items as $item)<tr><td>{{$item->batch?->medicine?->name}} / {{$item->batch?->batch_no}}</td><td>{{$item->pack_quantity}} × {{$item->units_per_pack}}</td><td>{{$item->quantity}}</td><td>{{$item->hsn_code}}</td><td>{{$item->tax_rate}}%</td><td>₹{{number_format($item->line_total,2)}}</td></tr>@endforeach</tbody></table>
<p style="text-align:right">Gross ₹{{number_format($sale->subtotal,2)}}<br>Discount ₹{{number_format($sale->discount_total,2)}}<br>Taxable (illustrative) ₹{{number_format($sale->taxable_total,2)}}<br>Included tax (illustrative) ₹{{number_format($sale->tax_total,2)}}<br><strong>Payable ₹{{number_format($sale->total,2)}}</strong></p>
<p><small>DEMO ONLY: No tax compliance verification, prescription sales, valid drug-sale register, payment gateway settlement or official GST invoice.</small></p></div>
@endsection
