<?php

namespace App\Http\Controllers;

use App\Services\TelegramLinkService;
use Illuminate\Http\Request;

class TelegramLinkController extends Controller
{
    public function __construct(private TelegramLinkService $telegramLinkService) {}

    public function generate(Request $request)
    {
        $result = $this->telegramLinkService->generateToken($request->user()->id);

        return response()->json($result);
    }
}
