<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\TelegramSession;
use App\Repositories\Contracts\TelegramLinkRepositoryInterface;
use App\Services\BalanceCalculator;
use App\Services\ExpenseService;
use App\Services\IncomeService;
use App\Services\SalaryService;
use App\Services\TelegramBotService;
use App\Services\TelegramLinkService;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function __construct(
        private TelegramLinkRepositoryInterface $telegramLinkRepo,
        private TelegramBotService $botService,
        private BalanceCalculator $calculator,
        private ExpenseService $expenseService,
        private IncomeService $incomeService,
        private SalaryService $salaryService,
        private TelegramLinkService $telegramLinkService
    ) {}

    public function handle(Request $request)
    {
        $update = $request->all();

        // Handle callback queries (button presses)
        if (isset($update['callback_query'])) {
            return $this->handleCallback($update['callback_query']);
        }

        $message = $update['message'] ?? $update['edited_message'] ?? null;
        if (! $message) return response()->json(['ok' => true]);

        $chatId = (string) ($message['chat']['id'] ?? '');
        $text   = trim($message['text'] ?? '');
        if (! $chatId || $text === '') return response()->json(['ok' => true]);

        // /start — always allowed
        if ($text === '/start') {
            $link = $this->telegramLinkRepo->findByChatId($chatId);
            if ($link && $link->telegram_chat_id) {
                $this->botService->sendMessage($chatId, "👋 Welcome back! What would you like to do?", $this->botService->mainMenu());
            } else {
                $this->botService->sendMessage($chatId, $this->welcomeMessage());
            }
            return response()->json(['ok' => true]);
        }

        // /link <token>
        if (preg_match('/^\/link\s+(\S+)$/i', $text, $m)) {
            $success = $this->telegramLinkService->linkAccount($m[1], $chatId);
            if ($success) {
                $this->botService->sendMessage($chatId, "✅ Account linked! What would you like to do?", $this->botService->mainMenu());
            } else {
                $this->botService->sendMessage($chatId, "❌ Invalid or expired token. Generate a new one from the web app.");
            }
            return response()->json(['ok' => true]);
        }

        // Check if linked
        $link = $this->telegramLinkRepo->findByChatId($chatId);
        if (! $link || ! $link->telegram_chat_id) {
            $this->botService->sendMessage($chatId, $this->linkingInstructions());
            return response()->json(['ok' => true]);
        }

        $userId  = $link->user_id;
        $session = TelegramSession::forChat($chatId);

        // Handle /menu or /cancel
        if (in_array($text, ['/menu', '/cancel'])) {
            $session->clear();
            $this->botService->sendMessage($chatId, "What would you like to do?", $this->botService->mainMenu());
            return response()->json(['ok' => true]);
        }

        // Handle /summary and /balance
        if ($text === '/summary') {
            $session->clear();
            $this->sendSummary($chatId, $userId);
            return response()->json(['ok' => true]);
        }
        if ($text === '/balance') {
            $session->clear();
            $this->sendBalance($chatId, $userId);
            return response()->json(['ok' => true]);
        }

        // Handle step-based input
        if ($session->step) {
            return $this->handleStep($chatId, $userId, $text, $session);
        }

        // No active session — show menu
        $this->botService->sendMessage($chatId, "What would you like to do?", $this->botService->mainMenu());
        return response()->json(['ok' => true]);
    }

    private function handleCallback(array $callbackQuery)
    {
        $chatId   = (string) $callbackQuery['message']['chat']['id'];
        $data     = $callbackQuery['data'];
        $queryId  = $callbackQuery['id'];

        $this->botService->answerCallbackQuery($queryId);

        $link = $this->telegramLinkRepo->findByChatId($chatId);
        if (! $link || ! $link->telegram_chat_id) {
            $this->botService->sendMessage($chatId, $this->linkingInstructions());
            return response()->json(['ok' => true]);
        }

        $userId  = $link->user_id;
        $session = TelegramSession::forChat($chatId);

        // Cancel
        if ($data === 'cancel') {
            $session->clear();
            $this->botService->sendMessage($chatId, "Cancelled. What would you like to do?", $this->botService->mainMenu());
            return response()->json(['ok' => true]);
        }

        // Main menu choices
        if (str_starts_with($data, 'menu:')) {
            $choice = substr($data, 5);
            $session->clear();

            switch ($choice) {
                case 'expense':
                    $categories = Category::orderBy('is_default', 'desc')->orderBy('name')->get()->toArray();
                    $session->setStep('expense:category', []);
                    $this->botService->sendMessage($chatId, "💸 <b>Add Expense</b>\n\nChoose a category:", $this->botService->categoryKeyboard($categories));
                    break;

                case 'income':
                    $session->setStep('income:description', []);
                    $this->botService->sendMessage($chatId, "💵 <b>Add Income</b>\n\nEnter a description (e.g. freelance, bonus):", $this->botService->cancelKeyboard());
                    break;

                case 'salary':
                    $session->setStep('salary:month', []);
                    $this->botService->sendMessage($chatId, "💼 <b>Set Salary</b>\n\nChoose the month:", $this->botService->monthKeyboard());
                    break;

                case 'summary':
                    $this->sendSummary($chatId, $userId);
                    break;

                case 'balance':
                    $this->sendBalance($chatId, $userId);
                    break;
            }
            return response()->json(['ok' => true]);
        }

        // Category selection for expense
        if (str_starts_with($data, 'cat:')) {
            [, $catId, $catName] = explode(':', $data, 3);
            $session->setStep('expense:description', ['category_id' => (int)$catId, 'category_name' => $catName]);
            $this->botService->sendMessage($chatId, "📝 Category: <b>{$catName}</b>\n\nEnter a description:", $this->botService->cancelKeyboard());
            return response()->json(['ok' => true]);
        }

        // Date selection
        if (str_starts_with($data, 'date:')) {
            $dateChoice = substr($data, 5);
            $step       = $session->step;
            $sessionData = $session->data ?? [];

            if ($dateChoice === 'today') {
                $period = date('Y-m');
                $sessionData['period'] = $period;
            } elseif ($dateChoice === 'yesterday') {
                $period = date('Y-m', strtotime('-1 day'));
                $sessionData['period'] = $period;
            } elseif ($dateChoice === 'manual') {
                // Ask for manual date
                $type = str_starts_with($step, 'expense') ? 'expense' : 'income';
                $session->setStep("{$type}:date_manual", $sessionData);
                $this->botService->sendMessage($chatId, "Enter the date (YYYY-MM or YYYY-MM-DD):", $this->botService->cancelKeyboard());
                return response()->json(['ok' => true]);
            }

            // Move to price step
            $type = str_starts_with($step ?? '', 'expense') ? 'expense' : 'income';
            $session->setStep("{$type}:amount", $sessionData);
            $this->botService->sendMessage($chatId, "💰 Enter the amount (Rp):", $this->botService->cancelKeyboard());
            return response()->json(['ok' => true]);
        }

        // Month selection for salary
        if (str_starts_with($data, 'month:')) {
            $month = substr($data, 6);
            $session->setStep('salary:amount', ['period' => $month]);
            $this->botService->sendMessage($chatId, "💼 Month: <b>{$month}</b>\n\nEnter your salary amount (Rp):", $this->botService->cancelKeyboard());
            return response()->json(['ok' => true]);
        }

        return response()->json(['ok' => true]);
    }

    private function handleStep(string $chatId, int $userId, string $text, TelegramSession $session): \Illuminate\Http\JsonResponse
    {
        $step        = $session->step;
        $sessionData = $session->data ?? [];

        switch ($step) {
            // EXPENSE: description
            case 'expense:description':
                $sessionData['label'] = $text;
                $session->setStep('expense:date', $sessionData);
                $this->botService->sendMessage($chatId, "📅 When was this expense?", $this->botService->dateKeyboard());
                break;

            // EXPENSE: manual date
            case 'expense:date_manual':
                $period = $this->parsePeriod($text);
                if (! $period) {
                    $this->botService->sendMessage($chatId, "❌ Invalid date. Use YYYY-MM format (e.g. 2026-04):", $this->botService->cancelKeyboard());
                    break;
                }
                $sessionData['period'] = $period;
                $session->setStep('expense:amount', $sessionData);
                $this->botService->sendMessage($chatId, "💰 Enter the amount (Rp):", $this->botService->cancelKeyboard());
                break;

            // EXPENSE: amount → save
            case 'expense:amount':
                $amount = $this->parseAmount($text);
                if ($amount === null) {
                    $this->botService->sendMessage($chatId, "❌ Invalid amount. Enter a number (e.g. 15000):", $this->botService->cancelKeyboard());
                    break;
                }
                $period     = $sessionData['period'] ?? date('Y-m');
                $label      = $sessionData['label'] ?? 'Expense';
                $categoryId = $sessionData['category_id'] ?? null;
                $catName    = $sessionData['category_name'] ?? 'Uncategorized';

                $expense = $this->expenseService->create($userId, $label, $amount, $period, $categoryId);
                $summary = $this->calculator->calculate($userId, $period);
                $session->clear();

                $this->botService->sendMessage(
                    $chatId,
                    "✅ <b>Expense saved!</b>\n\n"
                    . "📂 {$catName}\n"
                    . "📝 {$label}\n"
                    . "📅 {$period}\n"
                    . "💸 " . $this->fmt($amount) . "\n\n"
                    . "💰 Balance: " . $this->fmt($summary['balance']),
                    $this->botService->mainMenu()
                );
                break;

            // INCOME: description
            case 'income:description':
                $sessionData['label'] = $text;
                $session->setStep('income:date', $sessionData);
                $this->botService->sendMessage($chatId, "📅 When did you receive this income?", $this->botService->dateKeyboard());
                break;

            // INCOME: manual date
            case 'income:date_manual':
                $period = $this->parsePeriod($text);
                if (! $period) {
                    $this->botService->sendMessage($chatId, "❌ Invalid date. Use YYYY-MM format:", $this->botService->cancelKeyboard());
                    break;
                }
                $sessionData['period'] = $period;
                $session->setStep('income:amount', $sessionData);
                $this->botService->sendMessage($chatId, "💰 Enter the amount (Rp):", $this->botService->cancelKeyboard());
                break;

            // INCOME: amount → save
            case 'income:amount':
                $amount = $this->parseAmount($text);
                if ($amount === null) {
                    $this->botService->sendMessage($chatId, "❌ Invalid amount. Enter a number:", $this->botService->cancelKeyboard());
                    break;
                }
                $period  = $sessionData['period'] ?? date('Y-m');
                $label   = $sessionData['label'] ?? 'Income';

                $income  = $this->incomeService->create($userId, $label, $amount, $period);
                $summary = $this->calculator->calculate($userId, $period);
                $session->clear();

                $this->botService->sendMessage(
                    $chatId,
                    "✅ <b>Income saved!</b>\n\n"
                    . "📝 {$label}\n"
                    . "📅 {$period}\n"
                    . "💵 +" . $this->fmt($amount) . "\n\n"
                    . "💰 Balance: " . $this->fmt($summary['balance']),
                    $this->botService->mainMenu()
                );
                break;

            // SALARY: amount → save
            case 'salary:amount':
                $amount = $this->parseAmount($text);
                if ($amount === null) {
                    $this->botService->sendMessage($chatId, "❌ Invalid amount. Enter a number:", $this->botService->cancelKeyboard());
                    break;
                }
                $period = $sessionData['period'] ?? date('Y-m');
                $this->salaryService->upsert($userId, $period, $amount);
                $session->clear();

                $this->botService->sendMessage(
                    $chatId,
                    "✅ <b>Salary set!</b>\n\n"
                    . "📅 {$period}\n"
                    . "💼 " . $this->fmt($amount),
                    $this->botService->mainMenu()
                );
                break;

            default:
                $session->clear();
                $this->botService->sendMessage($chatId, "What would you like to do?", $this->botService->mainMenu());
        }

        return response()->json(['ok' => true]);
    }

    private function sendSummary(string $chatId, int $userId): void
    {
        $period  = date('Y-m');
        $summary = $this->calculator->calculate($userId, $period);
        $this->botService->sendMessage(
            $chatId,
            "📊 <b>Summary — {$period}</b>\n\n"
            . "💼 Salary: " . $this->fmt($summary['salary']) . "\n"
            . "💵 Income: " . $this->fmt($summary['total_income']) . "\n"
            . "💸 Expenses: " . $this->fmt($summary['total_expenses']) . "\n"
            . "💰 Balance: " . $this->fmt($summary['balance']),
            $this->botService->mainMenu()
        );
    }

    private function sendBalance(string $chatId, int $userId): void
    {
        $period  = date('Y-m');
        $summary = $this->calculator->calculate($userId, $period);
        $this->botService->sendMessage(
            $chatId,
            "💰 Balance: " . $this->fmt($summary['balance']),
            $this->botService->mainMenu()
        );
    }

    private function fmt(?float $amount): string
    {
        if ($amount === null) return 'N/A';
        return 'Rp ' . number_format($amount, 0, '.', ',');
    }

    private function parseAmount(string $text): ?float
    {
        $clean = preg_replace('/[^0-9.,]/', '', $text);
        if (preg_match('/^\d{1,3}([.,]\d{3})+$/', $clean)) {
            return (float) preg_replace('/[.,]/', '', $clean);
        }
        if (preg_match('/^(\d+)[.,](\d{1,2})$/', $clean, $m)) {
            return (float) ($m[1] . '.' . $m[2]);
        }
        $n = (float) $clean;
        return $n > 0 ? $n : null;
    }

    private function parsePeriod(string $text): ?string
    {
        $text = trim($text);
        if (preg_match('/^\d{4}-\d{2}$/', $text)) return $text;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $text)) return substr($text, 0, 7);
        return null;
    }

    private function welcomeMessage(): string
    {
        return "👋 <b>Welcome to BudgetTrack Bot!</b>\n\n" . $this->linkingInstructions();
    }

    private function linkingInstructions(): string
    {
        $webUrl = config('app.url');
        return "To get started, link your account:\n\n"
            . "1️⃣ Open: <a href=\"{$webUrl}\">{$webUrl}</a>\n"
            . "2️⃣ Click <b>Link Telegram</b> in the sidebar\n"
            . "3️⃣ Send the token here:\n"
            . "   <code>/link your-token</code>";
    }
}
