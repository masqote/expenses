<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TelegramBotService
{
    private function token(): string
    {
        return config('services.telegram.bot_token');
    }

    public function sendMessage(string $chatId, string $text, ?array $replyMarkup = null): void
    {
        $payload = [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'HTML',
        ];

        if ($replyMarkup) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        Http::post("https://api.telegram.org/bot{$this->token()}/sendMessage", $payload);
    }

    public function answerCallbackQuery(string $callbackQueryId, string $text = ''): void
    {
        Http::post("https://api.telegram.org/bot{$this->token()}/answerCallbackQuery", [
            'callback_query_id' => $callbackQueryId,
            'text'              => $text,
        ]);
    }

    public function mainMenu(): array
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => '💸 Add Expense',  'callback_data' => 'menu:expense'],
                    ['text' => '💵 Add Income',   'callback_data' => 'menu:income'],
                ],
                [
                    ['text' => '💼 Set Salary',   'callback_data' => 'menu:salary'],
                    ['text' => '📊 Summary',       'callback_data' => 'menu:summary'],
                ],
                [
                    ['text' => '💰 Balance',       'callback_data' => 'menu:balance'],
                ],
            ],
        ];
    }

    public function categoryKeyboard(array $categories): array
    {
        $rows = [];
        $row  = [];
        foreach ($categories as $i => $cat) {
            $icon  = $cat['icon'] ?? '📦';
            $row[] = ['text' => "{$icon} {$cat['name']}", 'callback_data' => "cat:{$cat['id']}:{$cat['name']}"];
            if (count($row) === 2) {
                $rows[] = $row;
                $row    = [];
            }
        }
        if ($row) $rows[] = $row;
        $rows[] = [['text' => '❌ Cancel', 'callback_data' => 'cancel']];
        return ['inline_keyboard' => $rows];
    }

    public function cancelKeyboard(): array
    {
        return ['inline_keyboard' => [[['text' => '❌ Cancel', 'callback_data' => 'cancel']]]];
    }

    public function dateKeyboard(): array
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => '📅 Today',     'callback_data' => 'date:today'],
                    ['text' => '📅 Yesterday', 'callback_data' => 'date:yesterday'],
                ],
                [['text' => '✏️ Enter date manually', 'callback_data' => 'date:manual']],
                [['text' => '❌ Cancel', 'callback_data' => 'cancel']],
            ],
        ];
    }

    public function monthKeyboard(): array
    {
        $months = [];
        for ($i = 0; $i < 3; $i++) {
            $d       = now()->subMonths($i);
            $months[] = [
                'text'          => $d->format('M Y'),
                'callback_data' => 'month:' . $d->format('Y-m'),
            ];
        }
        return [
            'inline_keyboard' => [
                [$months[0], $months[1]],
                [$months[2]],
                [['text' => '❌ Cancel', 'callback_data' => 'cancel']],
            ],
        ];
    }
}
