<?php

namespace App\Services;

use App\Repositories\Contracts\TelegramLinkRepositoryInterface;
use Illuminate\Support\Str;

class TelegramLinkService
{
    public function __construct(
        private TelegramLinkRepositoryInterface $telegramLinkRepo
    ) {}

    public function generateToken(int $userId): array
    {
        $token     = Str::random(32);
        $hashed    = hash('sha256', $token);
        $expiresAt = now()->addDay();

        $this->telegramLinkRepo->upsertForUser($userId, [
            'link_token'       => $hashed,
            'token_used'       => false,
            'token_expires_at' => $expiresAt,
            'telegram_chat_id' => null,
        ]);

        return ['token' => $token, 'expires_at' => $expiresAt];
    }

    public function linkAccount(string $token, string $chatId): bool
    {
        $hashed = hash('sha256', $token);
        $link   = $this->telegramLinkRepo->findByToken($hashed);

        if (! $link || $link->token_used || $link->token_expires_at->isPast()) {
            return false;
        }

        $this->telegramLinkRepo->markTokenUsed($link, $chatId);

        return true;
    }
}
