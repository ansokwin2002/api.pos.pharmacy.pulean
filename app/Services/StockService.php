<?php

namespace App\Services;

use App\Models\Drug;

class StockService
{
    public function __construct(private readonly Drug $drugModel)
    {
    }

    /**
     * Deduct stock for a list of deductions.
     * Does NOT manage DB transactions — the caller decides commit/rollback.
     *
     * @param  array  $deductions  [{ drug_id, deducted_quantity, deduction_unit }]
     * @return array ['success' => bool, 'results' => array, 'errors' => array, 'message' => string]
     */
    public function deduct(array $deductions, bool $rollbackOnError = true): array
    {
        $results = [];
        $errors = [];

        foreach ($deductions as $deductionItem) {
            $drug = $this->drugModel->find($deductionItem['drug_id']);

            if (!$drug) {
                $errors[] = [
                    'drug_id' => $deductionItem['drug_id'] ?? null,
                    'message' => 'Drug not found',
                ];
                continue;
            }

            if ($drug->type_drug === 'box-only' && $deductionItem['deduction_unit'] !== 'box') {
                $errors[] = [
                    'drug_id' => $drug->id,
                    'message' => 'Invalid deduction unit for drug ' . $drug->name . '. Only "box" unit is allowed.',
                    'deduction_unit' => $deductionItem['deduction_unit'],
                ];
                continue;
            }

            // These defaults help avoid division by zero and ensure calculations work
            $stripsPerBox = $drug->strips_per_box ?? 1;
            $tabletsPerStrip = $drug->tablets_per_strip ?? 1;

            if ($stripsPerBox === 0) $stripsPerBox = 1;
            if ($tabletsPerStrip === 0) $tabletsPerStrip = 1;

            $tabletsToDeduct = 0;

            if ($deductionItem['deduction_unit'] === 'tablet') {
                $tabletsToDeduct = $deductionItem['deducted_quantity'];
            } elseif ($deductionItem['deduction_unit'] === 'strip') {
                $tabletsToDeduct = $deductionItem['deducted_quantity'] * $tabletsPerStrip;
            } elseif ($deductionItem['deduction_unit'] === 'box') {
                $tabletsToDeduct = $deductionItem['deducted_quantity'] * $stripsPerBox * $tabletsPerStrip;
            }

            // Check for sufficient stock
            if ($drug->total_tablets < $tabletsToDeduct) {
                $errors[] = [
                    'drug_id' => $drug->id,
                    'message' => 'Insufficient stock for drug: ' . $drug->name,
                    'available_tablets' => $drug->total_tablets,
                    'requested_deduction_in_tablets' => $tabletsToDeduct,
                ];
                continue; // Skip to next deduction item
            }

            // Perform deduction
            $drug->total_tablets -= $tabletsToDeduct;
            $drug->save();

            $results[] = [
                'drug_id' => $drug->id,
                'message' => 'Stock deducted successfully',
                'new_total_tablets' => $drug->total_tablets,
            ];
        }

        if (!empty($errors) && $rollbackOnError) {
            \Illuminate\Support\Facades\DB::rollBack();
            return [
                'success' => false,
                'results' => $results,
                'errors' => $errors,
                'message' => 'Some deductions failed due to insufficient stock',
            ];
        }

        return [
            'success' => empty($errors),
            'results' => $results,
            'errors' => $errors,
            'message' => empty($errors) ? 'All stock deductions processed successfully' : 'Some deductions failed due to insufficient stock',
        ];
    }
}