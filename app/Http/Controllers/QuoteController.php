<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    public function generatePdf(Request $request, int $logId): JsonResponse|\Illuminate\Http\Response
    {
        $log = Log::where('user_id', $request->user()->id)
            ->with('photos')
            ->find($logId);

        if (!$log) {
            return response()->json(['message' => 'Log not found'], 404);
        }

        $quoteNumber = 'Q-' . str_pad($log->id, 6, '0', STR_PAD_LEFT);
        $date = now()->format('F d, Y');

        $partsUsed = $log->parts_used ?? [];
        $partsTotal = 0;
        $partsLineItems = [];

        foreach ($partsUsed as $part) {
            $price = $this->extractPartPrice($part);
            $partsLineItems[] = [
                'name' => $part,
                'price' => $price,
            ];
            $partsTotal += $price;
        }

        $laborEstimate = $log->amount ? ($log->amount - $partsTotal) : 0;
        $totalEstimate = $log->estimated_price ?? $log->amount ?? ($partsTotal + $laborEstimate);

        $data = [
            'quote_number' => $quoteNumber,
            'date' => $date,
            'business_name' => 'Face-to-Action HVAC Services',
            'business_tagline' => 'Professional HVAC Solutions',
            'customer_name' => $log->customer_name ?? 'Valued Customer',
            'service_type' => $this->formatServiceType($log->service_type),
            'issue_type' => $this->formatIssueType($log->issue_type),
            'issue_description' => $log->transcribed_text ?? 'Service performed as per voice log.',
            'action_taken' => $log->action_taken ?? 'Service completed.',
            'parts_line_items' => $partsLineItems,
            'parts_subtotal' => $partsTotal,
            'labor_estimate' => max(0, $laborEstimate),
            'total_estimate' => $totalEstimate,
            'next_steps' => $log->next_steps,
            'notes' => 'This quote is valid for 30 days. Prices may vary based on final inspection.',
        ];

        $pdf = Pdf::loadView('quotes.pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = 'Quote_' . $quoteNumber . '_' . str_replace(' ', '_', $log->customer_name ?? 'Customer') . '.pdf';

        return $pdf->download($filename);
    }

    private function extractPartPrice(string $part): float
    {
        if (preg_match('/\$?\s*(\d+(?:,\d{3})*(?:\.\d{2})?)/', $part, $matches)) {
            return (float) str_replace(',', '', $matches[1]);
        }
        return 0;
    }

    private function formatServiceType(?string $type): string
    {
        if (!$type) return 'Service';
        return ucfirst($type);
    }

    private function formatIssueType(?string $type): string
    {
        if (!$type) return 'General Service';
        return ucwords(str_replace('_', ' ', $type));
    }
}
