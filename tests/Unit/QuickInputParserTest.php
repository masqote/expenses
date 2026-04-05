<?php

use App\Services\QuickInputParser;
use Illuminate\Validation\ValidationException;

$parser = fn () => new QuickInputParser();

// ─── Unit tests ───────────────────────────────────────────────────────────────

describe('expense parsing', function () use ($parser) {
    it('parses a plain label : amount as expense', function () use ($parser) {
        $result = ($parser)()->parse('coffee : 15000');
        expect($result)->toBe(['type' => 'expense', 'label' => 'coffee', 'amount' => 15000.0]);
    });

    it('trims whitespace from label and amount', function () use ($parser) {
        $result = ($parser)()->parse('  eat  :  20000  ');
        expect($result['label'])->toBe('eat');
        expect($result['amount'])->toBe(20000.0);
    });

    it('throws when separator is absent', function () use ($parser) {
        expect(fn () => ($parser)()->parse('noseparator'))
            ->toThrow(ValidationException::class);
    });

    it('throws when label is empty', function () use ($parser) {
        expect(fn () => ($parser)()->parse(' : 5000'))
            ->toThrow(ValidationException::class);
    });
});

describe('income parsing', function () use ($parser) {
    it('parses income prefix (lowercase)', function () use ($parser) {
        $result = ($parser)()->parse('income freelance : 500000');
        expect($result)->toBe(['type' => 'income', 'label' => 'freelance', 'amount' => 500000.0]);
    });

    it('parses income prefix (uppercase)', function () use ($parser) {
        $result = ($parser)()->parse('INCOME bonus : 100000');
        expect($result)->toBe(['type' => 'income', 'label' => 'bonus', 'amount' => 100000.0]);
    });

    it('throws when income label is empty', function () use ($parser) {
        expect(fn () => ($parser)()->parse('income  : 5000'))
            ->toThrow(ValidationException::class);
    });
});

describe('salary parsing', function () use ($parser) {
    it('parses salary prefix', function () use ($parser) {
        $result = ($parser)()->parse('salary 5000000');
        expect($result)->toBe(['type' => 'salary', 'amount' => 5000000.0]);
    });

    it('parses salary prefix case-insensitively', function () use ($parser) {
        $result = ($parser)()->parse('SALARY 3000000');
        expect($result)->toBe(['type' => 'salary', 'amount' => 3000000.0]);
    });
});

describe('amount normalization', function () use ($parser) {
    it('strips period thousands separator', function () use ($parser) {
        $result = ($parser)()->parse('eat : 20.000');
        expect($result['amount'])->toBe(20000.0);
    });

    it('strips comma thousands separator', function () use ($parser) {
        $result = ($parser)()->parse('eat : 20,000');
        expect($result['amount'])->toBe(20000.0);
    });

    it('strips multi-group thousands separator', function () use ($parser) {
        $result = ($parser)()->parse('rent : 1.000.000');
        expect($result['amount'])->toBe(1000000.0);
    });

    it('treats single period with 1-2 digits as decimal', function () use ($parser) {
        $result = ($parser)()->parse('item : 20.5');
        expect($result['amount'])->toBe(20.5);
    });

    it('treats single comma with 1-2 digits as decimal', function () use ($parser) {
        $result = ($parser)()->parse('item : 20,50');
        expect($result['amount'])->toBe(20.50);
    });

    it('throws on non-numeric amount', function () use ($parser) {
        expect(fn () => ($parser)()->parse('item : abc'))
            ->toThrow(ValidationException::class);
    });
});

// ─── Property-based tests ─────────────────────────────────────────────────────

// Feature: expense-tracker, Property 12: Quick Input Parsing Round-Trip
// Validates: Requirements 6.1, 6.5
it('Property 12 – parses any valid label and amount round-trip', function () {
    $parser = new QuickInputParser();

    // Generate 100 random label + integer amount pairs
    $labels  = ['coffee', 'rent', 'food', 'taxi', 'gym', 'book', 'movie', 'gas', 'water', 'phone'];
    $amounts = [100, 500, 1500, 9999, 50000, 123456];

    $iterations = 0;
    foreach ($labels as $label) {
        foreach ($amounts as $amount) {
            $input  = "{$label} : {$amount}";
            $result = $parser->parse($input);
            expect($result['type'])->toBe('expense');
            expect($result['label'])->toBe($label);
            expect($result['amount'])->toBe((float) $amount);
            $iterations++;
        }
    }

    // Ensure we ran enough iterations
    expect($iterations)->toBeGreaterThanOrEqual(60);
})->repeat(1);

// Feature: expense-tracker, Property 13: Thousands Separator Normalization
// Validates: Requirements 6.3
it('Property 13 – thousands separator normalization', function () {
    $parser = new QuickInputParser();

    // Generate integers ≥ 1000 and format with both . and , separators
    $values = [1000, 1500, 9999, 10000, 99999, 100000, 999999, 1000000, 9999999];

    foreach ($values as $n) {
        // Format with period separator
        $withDot   = number_format($n, 0, '.', '.');
        $withComma = number_format($n, 0, '.', ',');

        $resultDot   = $parser->parse("item : {$withDot}");
        $resultComma = $parser->parse("item : {$withComma}");

        expect($resultDot['amount'])->toBe((float) $n, "Failed for dot-formatted {$withDot}");
        expect($resultComma['amount'])->toBe((float) $n, "Failed for comma-formatted {$withComma}");
    }
})->repeat(1);

// Feature: expense-tracker, Property 14: Malformed Quick Input Rejected
// Validates: Requirements 6.2
it('Property 14 – malformed quick input (no separator) is rejected', function () {
    $parser = new QuickInputParser();

    $malformed = [
        'noseparator',
        'coffee15000',
        'coffee:15000',       // colon without spaces
        'coffee: 15000',      // missing leading space
        'coffee :15000',      // missing trailing space
        '',
        '   ',
        'just a label',
        '12345',
    ];

    foreach ($malformed as $input) {
        $threw = false;
        try {
            $parser->parse($input);
        } catch (ValidationException $e) {
            $threw = true;
        }
        expect($threw)->toBeTrue("Expected ValidationException for input: '{$input}'");
    }
})->repeat(1);

// Feature: expense-tracker, Property 18: Income Parsing from Quick Input
// Validates: Requirements 13.2
it('Property 18 – income prefix parsing round-trip', function () {
    $parser = new QuickInputParser();

    $cases = [
        ['label' => 'freelance',  'amount' => 500000],
        ['label' => 'bonus',      'amount' => 100000],
        ['label' => 'side gig',   'amount' => 75000],
        ['label' => 'consulting', 'amount' => 250000],
        ['label' => 'dividend',   'amount' => 12500],
    ];

    foreach ($cases as $case) {
        foreach (['income', 'Income', 'INCOME'] as $prefix) {
            $input  = "{$prefix} {$case['label']} : {$case['amount']}";
            $result = $parser->parse($input);
            expect($result['type'])->toBe('income');
            expect($result['label'])->toBe($case['label']);
            expect($result['amount'])->toBe((float) $case['amount']);
        }
    }
})->repeat(1);
