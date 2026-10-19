<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** V9 illustrative reports; demo store #1 only. */
class PharmacyReportsController extends Controller
{
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'expiry_days' => ['nullable', 'integer', 'in:30,60,90,180'],
        ]);
        return [
            $validated['from'] ?? now()->subDays(29)->toDateString(),
            $validated['to'] ?? now()->toDateString(),
            (int) ($validated['expiry_days'] ?? 90),
        ];
    }

    private function data(string $from, string $to, int $days): array
    {
        $storeId = \App\Support\StoreContext::id();
        $sales = DB::table('sales')
            ->where('store_id', $storeId)->where('status', 'posted')
            ->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as invoices, SUM(total) as gross_sales, SUM(tax_total) as tax_total')
            ->groupByRaw('DATE(created_at)')->orderBy('day')->get();

        $purchases = DB::table('purchases as p')
            ->join('suppliers as s', 's.id', '=', 'p.supplier_id')
            ->leftJoin('supplier_payments as sp', 'sp.purchase_id', '=', 'p.id')
            ->where('p.store_id', $storeId)->where('p.status', 'posted')
            ->groupBy('p.id', 'p.invoice_no', 'p.invoice_date', 'p.total', 's.name')
            ->select('p.id', 'p.invoice_no', 'p.invoice_date', 's.name as supplier', 'p.total')
            ->selectRaw('COALESCE(SUM(sp.amount),0) as amount_paid')
            ->orderByDesc('p.invoice_date')->get()->map(function ($row) {
                $row->outstanding = round(max(0, (float)$row->total - (float)$row->amount_paid), 2);
                return $row;
            });

        $stock = DB::table('stock_balances as sb')
            ->join('medicine_batches as b', 'b.id', '=', 'sb.medicine_batch_id')
            ->join('medicines as m', 'm.id', '=', 'b.medicine_id')
            ->where('sb.store_id', $storeId)->where('sb.quantity', '>', 0)
            ->select('m.name as medicine', 'b.batch_no', 'b.expiry_date', 'sb.quantity', 'b.mrp_per_unit', 'b.id as batch_id')
            ->orderBy('b.expiry_date')->get();

        $cutoff = now()->addDays($days)->toDateString();
        $expiring = $stock->filter(fn($row) => $row->expiry_date <= $cutoff)->values();

        // Illustrative estimate only: last received purchase rate, not FIFO/weighted average/COGS.
        $latestCostByBatch = DB::table('purchase_items as pi')
            ->join('purchases as p', 'p.id', '=', 'pi.purchase_id')
            ->where('p.store_id', $storeId)->where('p.status', 'posted')
            ->select('pi.medicine_batch_id', 'pi.cost_per_unit', 'pi.id')
            ->orderByDesc('pi.id')->get()->unique('medicine_batch_id')->keyBy('medicine_batch_id');
        $stock = $stock->map(function ($row) use ($latestCostByBatch) {
            $cost = $latestCostByBatch->get($row->batch_id)?->cost_per_unit;
            $row->last_purchase_cost = $cost;
            $row->estimated_value = $cost === null ? null : round((float)$cost * (int)$row->quantity, 2);
            return $row;
        });
        $expiring = $stock->filter(fn($row) => $row->expiry_date <= $cutoff)->values();

        $financials = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('je.store_id', $storeId)
            ->select('a.code', 'a.name', 'a.type')
            ->selectRaw('SUM(jl.debit) as debit, SUM(jl.credit) as credit')
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type')->orderBy('a.code')->get();

        $unpostedSales = DB::table('sales as s')->leftJoin('journal_entries as j', function($join) {
                $join->on('j.reference_id', '=', 's.id')->where('j.reference_type', '=', 'sale');
            })->where('s.store_id', $storeId)->where('s.status', 'posted')->whereNull('j.id')->count();
        $unpostedPurchases = DB::table('purchases as p')->leftJoin('journal_entries as j', function($join) {
                $join->on('j.reference_id', '=', 'p.id')->where('j.reference_type', '=', 'purchase');
            })->where('p.store_id', $storeId)->where('p.status', 'posted')->whereNull('j.id')->count();

        return compact('sales', 'purchases', 'stock', 'expiring', 'financials', 'unpostedSales', 'unpostedPurchases');
    }

    public function index(Request $request)
    {
        [$from, $to, $days] = $this->filters($request);
        $data = $this->data($from, $to, $days);
        return view('reports.index', [...$data, 'from'=>$from, 'to'=>$to, 'days'=>$days]);
    }

    public function export(Request $request, string $type): StreamedResponse
    {
        abort_unless(in_array($type, ['sales', 'outstanding', 'stock', 'expiry', 'trial-balance'], true), 404);
        [$from, $to, $days] = $this->filters($request);
        $result = $this->data($from, $to, $days);
        [$headers, $rows] = match ($type) {
            'sales' => [['Date','Invoices','Gross Sales','Tax (Snapshot)'], $result['sales']->map(fn($r)=>[$r->day,$r->invoices,$r->gross_sales,$r->tax_total])],
            'outstanding' => [['Supplier','Purchase Invoice','Invoice Date','Gross Due','Paid','Remaining (Excludes Return Credits)'], $result['purchases']->map(fn($r)=>[$r->supplier,$r->invoice_no,$r->invoice_date,$r->total,$r->amount_paid,$r->outstanding])],
            'stock' => [['Medicine','Batch','Expiry','Units','Last Purchase Cost','Est Value'], $result['stock']->map(fn($r)=>[$r->medicine,$r->batch_no,$r->expiry_date,$r->quantity,$r->last_purchase_cost,$r->estimated_value])],
            'expiry' => [['Medicine','Batch','Expiry','Units'], $result['expiring']->map(fn($r)=>[$r->medicine,$r->batch_no,$r->expiry_date,$r->quantity])],
            'trial-balance' => [['Account Code','Account Name','Type','Debit','Credit'], $result['financials']->map(fn($r)=>[$r->code,$r->name,$r->type,$r->debit,$r->credit])],
        };
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM for Excel compatibility.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                // Avoid spreadsheet formula injection from supplier/medicine-controlled text.
                $safe = array_map(function ($value) {
                    $text = (string) ($value ?? '');
                    return preg_match('/^[\x00-\x20]*[=+@-]/u', $text) ? "'".$text : $text;
                }, $row);
                fputcsv($out, $safe);
            }
            fclose($out);
        }, 'pharmacy-'.$type.'-'.now()->format('Ymd').'.csv', ['Content-Type'=>'text/csv; charset=UTF-8']);
    }
}
