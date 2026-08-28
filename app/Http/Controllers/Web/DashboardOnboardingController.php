<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\SetupChecklistService;
use Illuminate\View\View;

class DashboardOnboardingController extends Controller
{
    public function index(SetupChecklistService $service): View
    {
        $items = $service->items();

        return view('dashboard.onboarding', [
            'items' => $items,
            'doneCount' => collect($items)->filter(fn (array $item) => $item['done'])->count(),
            'totalCount' => count($items),
            'setupPercent' => $service->completionPercent(),
            'setupComplete' => $service->isComplete(),
        ]);
    }
}
