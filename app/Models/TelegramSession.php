<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramSession extends Model
{
    protected $fillable = ['chat_id', 'step', 'data'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public static function forChat(string $chatId): self
    {
        return self::firstOrCreate(['chat_id' => $chatId], ['step' => null, 'data' => []]);
    }

    public function setStep(?string $step, array $data = []): void
    {
        $this->update(['step' => $step, 'data' => $data]);
    }

    public function mergeData(array $extra): void
    {
        $this->update(['data' => array_merge($this->data ?? [], $extra)]);
    }

    public function clear(): void
    {
        $this->update(['step' => null, 'data' => []]);
    }
}
