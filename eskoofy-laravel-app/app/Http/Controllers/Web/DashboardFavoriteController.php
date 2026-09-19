<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DashboardFavorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DashboardFavoriteController extends Controller
{
    public function toggle(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|string|max:255',
            'label' => 'nullable|string|max:120',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid payload', 'errors' => $validator->errors()], 422);
        }

        $url = $request->input('url');
        $label = mb_substr($request->input('label') ?? '', 0, 120) ?: null;

        if (! str_starts_with($url, '/') && ! str_starts_with($url, url('/'))) {
            return response()->json(['message' => 'Invalid URL'], 422);
        }

        $existing = DashboardFavorite::where('user_id', $request->user()->id)->where('url', $url)->first();

        if ($existing) {
            $existing->delete();

            return response()->json(['favorite' => false]);
        }

        $this->pruneToLimit($request->user()->id, 11);

        DashboardFavorite::create([
            'user_id' => $request->user()->id,
            'url' => $url,
            'label' => $label,
        ]);

        return response()->json(['favorite' => true]);
    }

    private function pruneToLimit(int $userId, int $max = 12): void
    {
        $ids = DashboardFavorite::where('user_id', $userId)
            ->orderByDesc('updated_at')
            ->pluck('id');

        $overflow = $ids->slice($max)->values();
        if ($overflow->isNotEmpty()) {
            DashboardFavorite::whereIn('id', $overflow)->delete();
        }
    }
}
