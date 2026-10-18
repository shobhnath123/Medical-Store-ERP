@extends('layouts.app')
@section('content')
<h1>Multi-Item Purchase Entry (V4)</h1>
<p>Prices entered per selected pack. Received inventory is stored as smallest units (paid + free packs). Tax percentages are illustrative inputs and must be checked before use.</p>
<div class="card">
 <form method="POST" action="{{ route('purchases.store') }}">@csrf
  <div class="grid">
   <div><label>Supplier</label><select name="supplier_id" required><option value="">Choose supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>@endforeach</select></div>
   <div><label>Supplier Invoice Number</label><input name="invoice_no" value="{{ old('invoice_no') }}" required></div>
   <div><label>Date</label><input name="invoice_date" type="date" value="{{ old('invoice_date', date('Y-m-d')) }}" required></div>
  </div>
  <h3>Medicines</h3><div id="purchase-lines"></div>
  <button type="button" id="add-purchase-line">+ Add Medicine</button>
  <p><strong>Estimated total: ₹<span id="purchase-total">0.00</span></strong> (server recalculates on posting)</p>
  <button type="submit">Post Purchase &amp; Receive Stock</button>
 </form>
</div>
<div class="card"><h3>Recent Purchases</h3><table><thead><tr><th>Invoice</th><th>Date</th><th>Subtotal</th><th>Discount</th><th>Tax</th><th>Total</th></tr></thead><tbody>@foreach($rows as $row)<tr><td>{{ $row->invoice_no }}</td><td>{{ $row->invoice_date->format('Y-m-d') }}</td><td>{{ $row->subtotal }}</td><td>{{ $row->discount_total }}</td><td>{{ $row->tax_total }}</td><td>{{ $row->total }}</td></tr>@endforeach</tbody></table></div>
<script>
const medicines = @json($medicineOptions);
const lines = document.getElementById('purchase-lines');
let lineCounter = 0;
function addLine(){
 const id = lineCounter++;
 const line = document.createElement('fieldset'); line.className='purchase-line';line.style.marginBottom='18px';
 line.innerHTML = `<legend>Medicine ${id+1}</legend><div class="grid">
 <label>Medicine <select required name="items[${id}][medicine_id]" class="med"><option value="">Select</option>${medicines.map(m=>`<option value="${m.id}">${escapeHtml(m.name)}</option>`).join('')}</select></label>
 <label>Packing <select required name="items[${id}][medicine_packing_id]" class="packing"><option value="">Select medicine first</option></select></label>
 <label>Batch No. <input required name="items[${id}][batch_no]"></label>
 <label>Expiry (last day of month) <input required type="date" name="items[${id}][expiry_date]"></label>
 <label>Purchased packs <input required type="number" min="1" value="1" name="items[${id}][packs_purchased]" class="paid"></label>
 <label>Free packs <input type="number" min="0" value="0" name="items[${id}][free_packs]"></label>
 <label>Cost ₹/pack <input required type="number" min="0" step=".01" value="0" name="items[${id}][cost_per_pack]" class="cost"></label>
 <label>MRP ₹/pack <input required type="number" min=".01" step=".01" name="items[${id}][mrp_per_pack]"></label>
 <label>Selling ₹/pack <input required type="number" min="0" step=".01" name="items[${id}][sale_price_per_pack]"></label>
 <label>Discount % <input type="number" min="0" max="100" step=".01" value="0" name="items[${id}][discount_percent]" class="discount"></label>
 <label>Tax % (verify) <input type="number" min="0" max="100" step=".01" value="0" name="items[${id}][tax_rate]" class="tax"></label>
 </div><button type="button" class="remove-line">Remove</button>`;
 lines.append(line);
 line.querySelector('.med').addEventListener('change',e=>{
  const med=medicines.find(m=>String(m.id)===e.target.value);
  line.querySelector('.packing').innerHTML = '<option value="">Select packing</option>'+(med?.packings||[]).map(p=>`<option value="${p.id}">${escapeHtml(p.label)} (${p.units} base units)</option>`).join('');
 });
 line.querySelector('.remove-line').onclick=()=>{if(lines.children.length>1){line.remove();calculate();}};
 line.addEventListener('input', calculate); calculate();
}
function escapeHtml(s){return String(s).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;');}
function calculate(){let sum=0; lines.querySelectorAll('.purchase-line').forEach(l=>{
 const gross=(Number(l.querySelector('.paid').value)||0)*(Number(l.querySelector('.cost').value)||0);
 const taxable=gross*(1-(Number(l.querySelector('.discount').value)||0)/100);
 sum+=taxable*(1+(Number(l.querySelector('.tax').value)||0)/100);
});document.getElementById('purchase-total').textContent=sum.toFixed(2);}
document.getElementById('add-purchase-line').onclick=addLine;addLine();
</script>
@endsection
