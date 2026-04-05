<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class QuickInputParser
{
    /**
     * Parse a quick input string into a structured record.
     *
     * @throws ValidationException on malformed input
     * @return array{type: 'expense'|'income'|'salary', label: ?string, amount: float}
     */
    public function parse(string $input): array
    {
        $trimmed = trim($input);

        // Rule 1: income prefix (case-insensitive)
        if (preg_match('/^income\s+(.+)$/i', $trimmed, $m)) {
            $remainder = $m[1];
            ['label' => $label, 'amount' => $amount] = $this->parseLabelAmount($remainder);
            return ['type' => 'income', 'label' => $label, 'amount' => $amount];
        }

        // Rule 2: salary prefix (case-insensitive)
        if (preg_match('/^salary\s+(.+)$/i', $trimmed, $m)) {
            $amount = $this->parseAmount(trim($m[1]));
            return ['type' => 'salary', 'amount' => $amount];
        }

        // Rule 3: plain expense
        ['label' => $label, 'amount' => $amount] = $this->parseLabelAmount($trimmed);
        return ['type' => 'expense', 'label' => $label, 'amount' => $amount];
    }

    /**
     * Split on first ' : ', trim, validate, and parse amount.
     */
    private function parseLabelAmount(string $input): array
    {
        // Rule 4: split on first ' : ' (space-colon-space)
        $pos = strpos($input, ' : ');
        if ($pos === false) {
            throw ValidationException::withMessages([
                'input' => 'Invalid format. Expected: label : amount',
            ]);
        }

        $label  = trim(substr($input, 0, $pos));
        $rawAmt = trim(substr($input, $pos + 3));

        // Rule 7: reject empty label
        if ($label === '') {
            throw ValidationException::withMessages([
                'input' => 'Label cannot be empty.',
            ]);
        }

        $amount = $this->parseAmount($rawAmt);

        return ['label' => $label, 'amount' => $amount];
    }

    /**
     * Normalize and parse an amount string.
     *
     * Rules 5 & 6:
     *  - If it matches \d{1,3}([.,]\d{3})+ → thousands-separated integer, strip separators
     *  - If it contains a single '.' or ',' with < 3 trailing digits → decimal separator, replace with '.'
     *  - Otherwise parse as-is
     */
    private function parseAmount(string $raw): float
    {
        if ($raw === '') {
            throw ValidationException::withMessages([
                'input' => 'Amount cannot be empty.',
            ]);
        }

        // Rule 5: thousands-separated integer pattern e.g. 1.000 / 1,000 / 1.000.000
        if (preg_match('/^\d{1,3}([.,]\d{3})+$/', $raw)) {
            $normalized = preg_replace('/[.,]/', '', $raw);
            return (float) $normalized;
        }

        // Rule 6: single separator with < 3 trailing digits → decimal
        if (preg_match('/^(\d+)[.,](\d{1,2})$/', $raw, $m)) {
            return (float) ($m[1] . '.' . $m[2]);
        }

        // Plain integer or float (e.g. "20000", "20.5", "20.50")
        if (!is_numeric($raw)) {
            throw ValidationException::withMessages([
                'input' => 'Amount must be a valid number.',
            ]);
        }

        return (float) $raw;
    }
}
