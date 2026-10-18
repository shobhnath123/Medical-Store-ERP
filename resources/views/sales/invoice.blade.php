@extends('layouts.app')
@section('content')
<style>@media print { header, .no-print {display:none!important}body{background:white} main{max-width:none;margin:0} .card{box-shadow:none;padding:0}}</style>
<div class="no-print"><a href="{{route('sales.index')}}">← Back to POS</a> <button onclick="window.print()">Print Invoice</button></div>
<div class="card"><h1>Pharmacy Sale Receipt</h1><p><strong>Invoice:</strong> {{$sale->invoice_no}}<br><strong>Date:</strong> {{$sale->created_at->format('d-m-Y H:i')}}<br><strong>Customer:</strong> {{$sale->customer_name ?: 'Walk-in'}}<br><strong>Payment:</strong> {{strtoupper($sale->payment_method)}}</p>
<table><thead><tr><th>Medicine</th><th>Batch</th><th>Qty (units)</th><th>Unit price</th><th>Discount</th><th>Line total</th></tr></thead><tbody>@foreach($items as $item)<tr><td>{{$item->batch?->medicine?->name}}</td><td>{{$item->batch?->batch_no}}</td><td>{{$item->quantity}}</td><td>₹{{number_format($item->unit_price,2)}}</td><td>₹{{number_format($item->discount_amount,2)}}</td><td>₹{{number_format($item->line_total,2)}}</td></tr>@endforeach</tbody></table>
<p style="text-align:right">Subtotal ₹{{number_format($sale->subtotal,2)}}<br>Discount ₹{{number_format($sale->discount_total,2)}}<br><strong>Total ₹{{number_format($sale->total,2)}}</strong></p><small>DEMO RECEIPT ONLY — Not a compliant GST tax invoice. No regulated-drug sale without the required verification and records.</small></div>
@endsection
