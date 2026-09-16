<?php

namespace App\Http\Controllers;

use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\InvoiceSequence;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public const PAYMENT_METHODS = ['cash', 'aba', 'acleda', 'wings', 'pipay', 'other'];

    public function store(Request $request)
    {
        $data = $request->validate([
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'payment_method' => ['required', 'string', 'in:' . implode(',', self::PAYMENT_METHODS)],
            'status' => ['nullable', 'string', 'in:completed,pending,cancelled,refunded,deleted'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.drug_id' => ['nullable', 'integer', 'exists:drugs,id'],
            'items.*.drug_name' => ['required', 'string', 'max:255'],
            'items.*.unit_type' => ['nullable', 'string', 'max:50'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ]);

        $invoiceNumber = $data['invoice_number'] ?? $this->generateInvoiceNumber();

        $order = SaleOrder::create([
            'invoice_number' => $invoiceNumber,
            'customer_name' => $data['customer_name'] ?: 'Walk-in Customer',
            'customer_phone' => $data['customer_phone'] ?? null,
            'payment_method' => $data['payment_method'],
            'status' => $data['status'] ?? 'completed',
            'subtotal' => $data['subtotal'],
            'discount' => $data['discount'] ?? 0,
            'tax' => $data['tax'] ?? 0,
            'total' => $data['total'],
        ]);

        foreach ($data['items'] as $item) {
            SaleOrderItem::create([
                'sales_order_id' => $order->id,
                'drug_id' => $item['drug_id'] ?? null,
                'drug_name' => $item['drug_name'],
                'unit_type' => $item['unit_type'] ?? null,
                'price' => $item['price'],
                'qty' => $item['qty'],
                'subtotal' => $item['price'] * $item['qty'],
            ]);
        }

        return response()->json($order->load('items'), Response::HTTP_CREATED);
    }

    public function show(SaleOrder $sale)
    {
        return response()->json($sale->load('items'));
    }

    public function update(Request $request, SaleOrder $sale)
    {
        $data = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'payment_method' => ['required', 'string', 'in:' . implode(',', self::PAYMENT_METHODS)],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'in:completed,pending,cancelled,refunded,deleted'],
            'items' => ['sometimes', 'array'],
            'items.*.drug_name' => ['required_with:items', 'string', 'max:255'],
            'items.*.drug_id' => ['nullable', 'integer', 'exists:drugs,id'],
            'items.*.unit_type' => ['nullable', 'string', 'max:50'],
            'items.*.price' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.qty' => ['required_with:items', 'integer', 'min:1'],
        ]);

        $sale->update([
            'customer_name' => $data['customer_name'] ?? $sale->customer_name,
            'customer_phone' => $data['customer_phone'] ?? $sale->customer_phone,
            'payment_method' => $data['payment_method'],
            'subtotal' => $data['subtotal'],
            'discount' => $data['discount'] ?? 0,
            'tax' => $data['tax'] ?? 0,
            'total' => $data['total'],
            'status' => $data['status'] ?? $sale->status,
        ]);

        if (isset($data['items'])) {
            $sale->items()->delete();
            foreach ($data['items'] as $item) {
                SaleOrderItem::create([
                    'sales_order_id' => $sale->id,
                    'drug_id' => $item['drug_id'] ?? null,
                    'drug_name' => $item['drug_name'],
                    'unit_type' => $item['unit_type'] ?? null,
                    'price' => $item['price'],
                    'qty' => $item['qty'],
                    'subtotal' => $item['price'] * $item['qty'],
                ]);
            }
        }

        return response()->json($sale->load('items'));
    }

    public function index(Request $request)
    {
        $query = SaleOrder::with('items')->orderByDesc('created_at');

        if ($date = $request->query('date')) {
            $query->whereDate('created_at', $date);
        }

        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        if ($paymentMethod = $request->query('payment_method')) {
            $query->where('payment_method', $paymentMethod);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        } else {
            $query->where('status', '!=', 'deleted');
        }

        if ($keyword = trim((string) $request->query('keyword'))) {
            $query->where(function ($q) use ($keyword) {
                $q->where('invoice_number', 'like', "%{$keyword}%")
                    ->orWhere('customer_name', 'like', "%{$keyword}%")
                    ->orWhere('customer_phone', 'like', "%{$keyword}%")
                    ->orWhereHas('items', fn ($iq) => $iq->where('drug_name', 'like', "%{$keyword}%"));
            });
        }

        $paginated = $query->paginate($request->query('per_page', 15));
        return response()->json($paginated);
    }

    public function report(Request $request)
    {
        $type = $request->query('type', 'daily');

        switch ($type) {
            case 'monthly':
                return $this->monthlyReport($request);
            case 'yearly':
                return $this->yearlyReport($request);
            case 'range':
                return $this->rangeReport($request);
            case 'daily':
            default:
                return $this->dailyReport($request);
        }
    }

    protected function dailyReport(Request $request)
    {
        $method = $request->query('payment_method');
        $limit = (int) $request->query('limit', 60);

        $query = SaleOrder::query()
            ->select(
                DB::raw("DATE(created_at) as date"),
                DB::raw("COUNT(*) as orders"),
                DB::raw("SUM(total) as revenue")
            )
            ->where('status', '!=', 'deleted')
            ->groupBy(DB::raw("DATE(created_at)"))
            ->orderByDesc('date')
            ->limit($limit);

        if ($method) $query->where('payment_method', $method);
        if ($from = $request->query('from')) $query->whereDate('created_at', '>=', $from);
        if ($to = $request->query('to')) $query->whereDate('created_at', '<=', $to);

        $rows = $query->get();

        $profits = $this->profitMap('day', null, null, $method);
        foreach ($rows as $row) {
            $row->profit = round($profits[$row->date] ?? 0, 2);
        }

        $totalRevenue = $rows->sum('revenue');
        $totalOrders = $rows->sum('orders');
        $totalProfit = $rows->sum('profit');

        return response()->json([
            'type' => 'daily',
            'rows' => $rows,
            'total_revenue' => round($totalRevenue, 2),
            'total_orders' => $totalOrders,
            'total_profit' => round($totalProfit, 2),
        ]);
    }

    protected function monthlyReport(Request $request)
    {
        $year = $request->query('year', now()->year);
        $method = $request->query('payment_method');

        $query = SaleOrder::query()
            ->select(
                DB::raw("YEAR(created_at) as year"),
                DB::raw("MONTH(created_at) as month"),
                DB::raw("COUNT(*) as orders"),
                DB::raw("SUM(total) as revenue")
            )
            ->where('status', '!=', 'deleted')
            ->whereYear('created_at', $year)
            ->groupBy(DB::raw("YEAR(created_at)"), DB::raw("MONTH(created_at)"))
            ->orderBy('month');

        if ($method) $query->where('payment_method', $method);

        $rows = $query->get();

        $profits = $this->profitMap('month', null, null, $method, (int) $year);
        foreach ($rows as $row) {
            $key = sprintf('%04d-%02d', $row->year, $row->month);
            $row->profit = round($profits[$key] ?? 0, 2);
        }

        return response()->json([
            'type' => 'monthly',
            'year' => (int) $year,
            'rows' => $rows,
            'total_revenue' => round($rows->sum('revenue'), 2),
            'total_orders' => $rows->sum('orders'),
            'total_profit' => round($rows->sum('profit'), 2),
        ]);
    }

    protected function yearlyReport(Request $request)
    {
        $method = $request->query('payment_method');

        $query = SaleOrder::query()
            ->select(
                DB::raw("YEAR(created_at) as year"),
                DB::raw("COUNT(*) as orders"),
                DB::raw("SUM(total) as revenue")
            )
            ->where('status', '!=', 'deleted')
            ->groupBy(DB::raw("YEAR(created_at)"))
            ->orderBy('year');

        if ($method) $query->where('payment_method', $method);
        if ($from = $request->query('from')) $query->whereDate('created_at', '>=', $from);
        if ($to = $request->query('to')) $query->whereDate('created_at', '<=', $to);

        $rows = $query->get();

        $from = $request->query('from');
        $to = $request->query('to');
        $profits = $this->profitMap('year', $from, $to, $method);
        foreach ($rows as $row) {
            $row->profit = round($profits[(string) $row->year] ?? 0, 2);
        }

        return response()->json([
            'type' => 'yearly',
            'rows' => $rows,
            'total_revenue' => round($rows->sum('revenue'), 2),
            'total_orders' => $rows->sum('orders'),
            'total_profit' => round($rows->sum('profit'), 2),
        ]);
    }

    protected function rangeReport(Request $request)
    {
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to = $request->query('to', now()->toDateString());
        $method = $request->query('payment_method');

        $query = SaleOrder::query()
            ->select(
                DB::raw("DATE(created_at) as date"),
                DB::raw("COUNT(*) as orders"),
                DB::raw("SUM(total) as revenue")
            )
            ->where('status', '!=', 'deleted')
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->groupBy(DB::raw("DATE(created_at)"))
            ->orderBy('date');

        if ($method) $query->where('payment_method', $method);

        $rows = $query->get();

        $profits = $this->profitMap('day', $from, $to, $method);
        foreach ($rows as $row) {
            $row->profit = round($profits[$row->date] ?? 0, 2);
        }

        return response()->json([
            'type' => 'range',
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
            'total_revenue' => round($rows->sum('revenue'), 2),
            'total_orders' => $rows->sum('orders'),
            'total_profit' => round($rows->sum('profit'), 2),
        ]);
    }

    public function profitMap(string $granularity, ?string $from = null, ?string $to = null, ?string $method = null, ?int $year = null): array
    {
        $query = SaleOrderItem::query()
            ->leftJoin('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->leftJoin('drugs', 'sales_order_items.drug_id', '=', 'drugs.id')
            ->where('sales_orders.status', '!=', 'deleted');

        if ($from) $query->whereDate('sales_orders.created_at', '>=', $from);
        if ($to) $query->whereDate('sales_orders.created_at', '<=', $to);
        if ($year) $query->whereYear('sales_orders.created_at', $year);
        if ($method) $query->where('sales_orders.payment_method', $method);

        $rows = $query->get([
            'sales_order_items.unit_type',
            'sales_order_items.price',
            'sales_order_items.qty',
            'sales_orders.created_at',
            'drugs.id as drug_id',
            'drugs.box_cost_price',
            'drugs.strip_cost_price',
            'drugs.tablet_cost_price',
        ]);

        $map = [];
        foreach ($rows as $row) {
            if ($row->unit_type === 'strip') {
                $cost = $row->strip_cost_price;
            } elseif ($row->unit_type === 'tablet') {
                $cost = $row->tablet_cost_price;
            } elseif ($row->unit_type === 'box') {
                $cost = $row->box_cost_price;
            } else {
                continue;
            }

            if ($cost === null || $cost === '') continue;

            $key = match ($granularity) {
                'year' => (string) Carbon::parse($row->created_at)->year,
                'month' => Carbon::parse($row->created_at)->format('Y-m'),
                default => Carbon::parse($row->created_at)->format('Y-m-d'),
            };
            $profit = (float) $row->price - (float) $cost;
            $map[$key] = ($map[$key] ?? 0) + $profit * (int) $row->qty;
        }

        return array_map(fn($v) => round($v, 2), $map);
    }

    protected function generateInvoiceNumber(): string
    {
        $type = 'pos';
        $dateKey = now()->format('Ymd');

        $seq = DB::transaction(function () use ($type, $dateKey) {
            $s = InvoiceSequence::where('type', $type)
                ->where('date_key', $dateKey)
                ->lockForUpdate()
                ->first();

            if (!$s) {
                $s = InvoiceSequence::create([
                    'type' => $type,
                    'date_key' => $dateKey,
                    'current_number' => 0,
                ]);
            }

            $s->increment('current_number');
            return $s;
        });

        return $dateKey . '-' . str_pad($seq->current_number, 2, '0', STR_PAD_LEFT);
    }
}
