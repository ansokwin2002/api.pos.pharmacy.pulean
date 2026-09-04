<?php

namespace App\Http\Controllers;

use App\Models\InvoiceSequence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function nextNumber(Request $request)
    {
        $type = $request->query('type', 'prescription');
        $dateKey = now()->format('Ymd');

        $sequence = DB::transaction(function () use ($type, $dateKey) {
            $seq = InvoiceSequence::where('type', $type)
                ->where('date_key', $dateKey)
                ->lockForUpdate()
                ->first();

            if (!$seq) {
                $seq = InvoiceSequence::create([
                    'type' => $type,
                    'date_key' => $dateKey,
                    'current_number' => 0,
                ]);
            }

            $seq->increment('current_number');
            return $seq;
        });

        $number = $dateKey . '-' . str_pad($sequence->current_number, 2, '0', STR_PAD_LEFT);

        return response()->json([
            'invoice_number' => $number,
            'sequence' => $sequence->current_number,
            'date_key' => $dateKey,
            'type' => $type,
        ]);
    }
}
