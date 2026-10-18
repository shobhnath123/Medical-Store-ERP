@extends('layouts.app')
@section('content')
<h1>Sales POS – Multi-Medicine Billing</h1>
<p><small>Demo: base units only (e.g. tablets). Tax calculation, invoice-compliant GST fields and regulatory checks are not yet implemented.</small></p>
<div class="card">
 <form method="post" action="{{ route('sales.store') }}" id="posForm">@csrf
  <div class="grid"><div><label>Customer</label><input name="customer_name" value="{{old('customer_name')}}" placeholder="Walk-in"></div><div><label>Payment method</label><select name="payment_method"><option>cash</option><option>upi</option><option>card</option></select></div><div><label>Reference (optional)</label><input name="payment_reference" maxlength="100"></div></div>
  <h2>Items</h2><div id="cart"></div><button type="button" id="addItem">+ Add Medicine</button>
  <div class="grid"><div><label>Invoice discount ₹</label><input name="discount" id="discount" type="number" min="0" step="0.01" value="0"></div><div><label>Estimated total ₹</label><h2 id="total">0.00</h2></div></div>
  <button type="submit" id="postInvoice">Post Invoice & Deduct Stock</button>
 </form>
</div>
<div class="card"><h2>Recent Invoices</h2><table><thead><tr><th>Invoice</th><th>Customer</th><th>Total</th><th>Action</th></tr></thead><tbody>@foreach($rows as $sale)<tr><td>{{ $sale->invoice_no ?? '#'.$sale->id }}</td><td>{{ $sale->customer_name ?: 'Walk-in' }}</td><td>₹{{number_format($sale->total,2)}}</td><td><a href="{{route('sales.show',$sale)}}">Print</a></td></tr>@endforeach</tbody></table></div>
<script>
const batches=@json($batches);
const cart=document.getElementById('cart');
let nextId=0;
function recalc(){let sum=0;cart.querySelectorAll('.pos-row').forEach(row=>{const chosen=batches.find(b=>String(b.id)===row.querySelector('select').value);const qty=Number(row.querySelector('.qty').value)||0;sum+=chosen? Math.round(Number(chosen.sale_price_per_unit)*100)*qty:0;});const dis=Math.round((Number(document.getElementById('discount').value)||0)*100);document.getElementById('total').textContent=(Math.max(0,sum-dis)/100).toFixed(2)}
function addRow(){const i=nextId++;const wrap=document.createElement('div');wrap.className='pos-row';wrap.style.cssText='display:grid;grid-template-columns:minmax(240px,1fr) 120px auto;align-items:end;gap:12px;margin:12px 0';const lab=document.createElement('label');lab.textContent='Medicine / Batch';const select=document.createElement('select');select.name=`items[${i}][batch_id]`;select.required=true;const blank=new Option('Select batch (FEFO expiry order)','');select.add(blank);batches.forEach(b=>select.add(new Option(`${b.name} | ${b.batch_no} | ${b.expiry_date} | ₹${b.sale_price_per_unit} | Stock ${b.quantity}`,b.id)));lab.appendChild(select);const qlab=document.createElement('label');qlab.textContent='Units';const qty=document.createElement('input');qty.type='number';qty.min='1';qty.max='1000000';qty.value='1';qty.required=true;qty.className='qty';qty.name=`items[${i}][quantity]`;qlab.appendChild(qty);const remove=document.createElement('button');remove.type='button';remove.textContent='Remove';remove.onclick=()=>{wrap.remove();recalc()};wrap.append(lab,qlab,remove);cart.appendChild(wrap);select.addEventListener('change',recalc);qty.addEventListener('input',recalc);recalc()}
document.getElementById('addItem').onclick=addRow;document.getElementById('discount').addEventListener('input',recalc);addRow();
document.getElementById('posForm').addEventListener('submit',event=>{const ids=[...cart.querySelectorAll('select')].map(s=>s.value);if(new Set(ids).size!==ids.length){event.preventDefault();alert('Duplicate batch rows are not allowed. Combine quantities.');return;}document.getElementById('postInvoice').disabled=true;});
</script>
@endsection
