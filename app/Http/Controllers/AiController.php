<?php

namespace App\Http\Controllers;

use App\Services\AiService;
use Illuminate\Http\Request;

class AiController extends Controller
{
    protected $aiService;

    public function __construct(AiService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function generateSeo(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'content' => 'required|string',
        ]);

        $meta = $this->aiService->generateSeoMeta(
            $request->input('title'),
            $request->input('content')
        );

        return response()->json($meta);
    }

    public function generateAltText(Request $request)
    {
        $request->validate([
            'image_url' => 'required|url',
        ]);

        $altText = $this->aiService->generateAltText(
            $request->input('image_url')
        );

        return response()->json(['alt_text' => $altText]);
    }
}
