<?php

namespace App\Http\Controllers;

use App\Models\CashDrawer;
use App\Models\CashDrawerMovement;
use App\Models\SaleOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CashDrawerController extends Controller
{
    public function today()
    {
        $today = now()->toDateString();
        $drawer = CashDrawer::with('movements')
            ->where('session_date', $today)
            ->orderByDesc('id')
            ->first();

        return response()->json($this->payload($drawer));
    }

    public function show(int $id)
    {
        $drawer = CashDrawer::with('movements')->find($id);
        if (!$drawer) {
            return response()->json(['message' => 'Cash drawer not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($this->payload($drawer));
    }

    public function history(Request $request)
    {
        $limit = (int) $request->query('limit', 30);
        $rows = CashDrawer::withCount('movements')
            ->where('is_closed', true)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function (CashDrawer $d) {
                $date = $d->session_date->toDateString();
                $summary = $this->daySummary($date);
                $profitMap = app(SaleController::class)->profitMap('day', $date, $date);

                return [
                    'id' => $d->id,
                    'session_date' => $date,
                    'opening_float' => $d->opening_float,
                    'opened_at' => optional($d->opened_at)->toIso8601String(),
                    'expected' => $d->expected,
                    'counted' => $d->counted,
                    'difference' => $d->difference,
                    'movement_count' => $d->movements_count,
                    'closed_at' => optional($d->closed_at)->toIso8601String(),
                    'orders' => $summary['orders'],
                    'revenue' => $summary['revenue'],
                    'by_method' => $summary['by_method'],
                    'profit' => $profitMap[$date] ?? 0,
                ];
            });

        return response()->json(['data' => $rows]);
    }

    public function open(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'opening_float' => ['required', 'numeric', 'min:0'],
            'opened_at' => ['nullable', 'date'],
        ]);

        $date = $data['date'];
        $existing = CashDrawer::where('session_date', $date)
            ->where('is_closed', false)
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            $existing->update([
                'opening_float' => $data['opening_float'],
                'opened_at' => $data['opened_at'] ?? $existing->opened_at ?? now(),
            ]);
            return response()->json($this->payload($existing));
        }

        $drawer = CashDrawer::create([
            'session_date' => $date,
            'opening_float' => $data['opening_float'],
            'opened_at' => $data['opened_at'] ?? now(),
            'is_closed' => false,
        ]);

        return response()->json($this->payload($drawer), Response::HTTP_CREATED);
    }

    public function movement(Request $request)
    {
        $this->validate($request, [
            'date' => ['required', 'date_format:Y-m-d'],
            'type' => ['required', 'string', 'max:30'],
            'label' => ['nullable', 'string', 'max:255'],
            'invoice' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'change' => ['nullable', 'numeric', 'min:0'],
            'client_id' => ['nullable', 'string', 'max:64'],
            'occurred_at' => ['nullable', 'date'],
        ]);

        $date = $request->input('date');

        if ($request->filled('client_id')) {
            $dup = CashDrawerMovement::where('client_id', $request->input('client_id'))->first();
            if ($dup) {
                return response()->json($this->payload($dup->drawer));
            }
        }

        $drawer = CashDrawer::where('session_date', $date)
            ->where('is_closed', false)
            ->orderByDesc('id')
            ->first();

        if (!$drawer) {
            $drawer = CashDrawer::create([
                'session_date' => $date,
                'opening_float' => 0,
                'opened_at' => now(),
                'is_closed' => false,
            ]);
        }

        CashDrawerMovement::create([
            'cash_drawer_id' => $drawer->id,
            'type' => $request->input('type'),
            'label' => $request->input('label'),
            'invoice' => $request->input('invoice'),
            'amount' => $request->input('amount'),
            'change' => $request->input('change', 0),
            'client_id' => $request->input('client_id'),
            'user' => $this->actorName($request),
            'occurred_at' => $request->input('occurred_at') ?? now(),
        ]);

        return response()->json($this->payload($drawer));
    }

    public function adjustments(Request $request)
    {
        $this->validate($request, [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'type' => ['nullable', 'string', 'max:30'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $from = $request->input('from');
        $to = $request->input('to');
        $type = $request->input('type');
        $limit = (int) $request->input('limit', 100);

        $query = CashDrawerMovement::query()
            ->whereIn('type', ['cash_in', 'cash_out'])
            ->with('drawer');

        if ($from) {
            $query->whereHas('drawer', fn ($q) => $q->where('session_date', '>=', $from));
        }
        if ($to) {
            $query->whereHas('drawer', fn ($q) => $q->where('session_date', '<=', $to));
        }
        if ($type && in_array($type, ['cash_in', 'cash_out'])) {
            $query->where('type', $type);
        }

        $rows = $query->orderByDesc('occurred_at')->limit($limit)->get()->map(function (CashDrawerMovement $m) {
            return [
                'id' => $m->id,
                'type' => $m->type,
                'label' => $m->label,
                'amount' => $m->amount,
                'user' => $m->user,
                'session_date' => $m->drawer?->session_date?->toDateString(),
                'occurred_at' => optional($m->occurred_at)->toIso8601String(),
            ];
        });

        $totalsList = CashDrawerMovement::query()
            ->whereIn('type', ['cash_in', 'cash_out']);

        if ($from) {
            $totalsList->whereHas('drawer', fn ($q) => $q->where('session_date', '>=', $from));
        }
        if ($to) {
            $totalsList->whereHas('drawer', fn ($q) => $q->where('session_date', '<=', $to));
        }

        $totals = $totalsList->selectRaw("type, COALESCE(SUM(amount), 0) as total")
            ->groupBy('type')
            ->pluck('total', 'type');

        return response()->json([
            'data' => $rows,
            'totals' => [
                'cash_in' => round((float) ($totals['cash_in'] ?? 0), 2),
                'cash_out' => round((float) ($totals['cash_out'] ?? 0), 2),
            ],
        ]);
    }

    protected function actorName(Request $request): ?string
    {
        try {
            $user = $request->user('sanctum') ?? $request->user();
            if ($user) {
                return $user->name ?: $user->username ?: 'user-' . $user->id;
            }
        } catch (\Throwable $e) {
            // fall through to frontend-provided value
        }

        $name = $request->input('user');
        return is_string($name) && trim($name) !== '' ? trim($name) : null;
    }

    public function close(Request $request)
    {
        $this->validate($request, [
            'date' => ['required', 'date_format:Y-m-d'],
            'counted' => ['required', 'numeric', 'min:0'],
        ]);

        $date = $request->input('date');
        $drawer = CashDrawer::where('session_date', $date)
            ->where('is_closed', false)
            ->orderByDesc('id')
            ->first();

        if (!$drawer) {
            return response()->json(['message' => 'No open cash drawer found for this date'], Response::HTTP_NOT_FOUND);
        }

        $counted = round((float) $request->input('counted'), 2);
        $expected = $drawer->expectedTotal();
        $drawer->update([
            'counted' => $counted,
            'expected' => $expected,
            'difference' => round($counted - $expected, 2),
            'is_closed' => true,
            'closed_at' => now(),
        ]);

        return response()->json($this->payload($drawer));
    }

    protected function payload(?CashDrawer $drawer)
    {
        if (!$drawer) {
            return [
                'drawer' => null,
                'expected' => 0,
                'today_profit' => $this->todayProfit(),
                'profit' => $this->todayProfit(),
                'summary' => $this->daySummary(now()->toDateString()),
            ];
        }

        $drawer->load('movements');
        $date = $drawer->session_date->toDateString();
        $profitMap = app(SaleController::class)->profitMap('day', $date, $date);

        return [
            'drawer' => [
                'id' => $drawer->id,
                'session_date' => $date,
                'opening_float' => $drawer->opening_float,
                'opened_at' => optional($drawer->opened_at)->toIso8601String(),
                'closed_at' => optional($drawer->closed_at)->toIso8601String(),
                'is_closed' => $drawer->is_closed,
                'expected' => $drawer->expected,
                'counted' => $drawer->counted,
                'difference' => $drawer->difference,
                'movements' => $drawer->movements->map(function (CashDrawerMovement $m) {
                    return [
                        'id' => $m->id,
                        'type' => $m->type,
                        'label' => $m->label,
                        'invoice' => $m->invoice,
                        'amount' => $m->amount,
                        'change' => $m->change,
                        'client_id' => $m->client_id,
                        'user' => $m->user,
                        'occurred_at' => optional($m->occurred_at)->toIso8601String(),
                    ];
                }),
            ],
            'cash_sales' => $drawer->cashSalesTotal(),
            'change_given' => $drawer->changeGivenTotal(),
            'expected' => $drawer->expectedTotal(),
            'today_profit' => $this->todayProfit(),
            'profit' => $profitMap[$date] ?? 0,
            'summary' => $this->daySummary($date),
        ];
    }

    protected function daySummary(string $date): array
    {
        $orders = SaleOrder::whereDate('created_at', $date)
            ->where('status', 'completed')
            ->selectRaw("COUNT(*) as orders, COALESCE(SUM(total), 0) as revenue")
            ->first();

        $byMethod = [];
        foreach (SaleOrder::whereDate('created_at', $date)
            ->where('status', 'completed')
            ->groupBy('payment_method')
            ->selectRaw("payment_method, COUNT(*) as orders, COALESCE(SUM(total), 0) as revenue")
            ->get() as $m) {
            $byMethod[$m->payment_method ?? 'other'] = [
                'orders' => (int) $m->orders,
                'revenue' => round((float) $m->revenue, 2),
            ];
        }

        return [
            'date' => $date,
            'orders' => (int) ($orders->orders ?? 0),
            'revenue' => round((float) ($orders->revenue ?? 0), 2),
            'by_method' => $byMethod,
        ];
    }

    protected function todayProfit(): float
    {
        $date = now()->toDateString();
        $map = app(SaleController::class)->profitMap('day', $date, $date);
        return $map[$date] ?? 0;
    }
}