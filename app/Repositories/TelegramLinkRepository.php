<?php

namespace App\Repositories;

use App\Models\TelegramLink;
use App\Repositories\Contracts\TelegramLinkRepositoryInterface;

class TelegramLinkRepository implements TelegramLinkRepositoryInterface
{
    public function findByUserId(int $userId): ?TelegramLink
    {
        return TelegramLink::where('user_id', $userId)->first();
    }

    public function findByChatId(string $chatId): ?TelegramLink
    {
        return TelegramLink::where('telegram_chat_id', $chatId)->first();
    }

    public function findByToken(string $hashedToken): ?TelegramLink
    {
        return TelegramLink::where('link_token', $hashedToken)->first();
    }

    public function upsertForUser(int $userId, array $data): TelegramLink
    {
        return TelegramLink::updateOrCreate(
            ['user_id' => $userId],
            $data
        );
    }

    public function markTokenUsed(TelegramLink $link, string $chatId): void
    {
        $link->update([
            'token_used'        => true,
            'telegram_chat_id'  => $chatId,
        ]);
    }
}
