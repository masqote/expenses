<?php

namespace App\Repositories\Contracts;

use App\Models\TelegramLink;

interface TelegramLinkRepositoryInterface
{
    public function findByUserId(int $userId): ?TelegramLink;

    public function findByChatId(string $chatId): ?TelegramLink;

    public function findByToken(string $hashedToken): ?TelegramLink;

    public function upsertForUser(int $userId, array $data): TelegramLink;

    public function markTokenUsed(TelegramLink $link, string $chatId): void;
}
