<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GuardianController;
use App\Models\Guardian;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardGuardianController extends Controller
{
    public function create(Request $request): View
    {
        $this->authorize('create', Guardian::class);

        return view('dashboard.guardians.create');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $response = app(GuardianController::class)->store($request);
        if ($response instanceof JsonResponse && $response->getStatusCode() === 201) {
            return redirect()->route('dashboard.parents')->with('status', __('Parent / guardian created.'));
        }

        return $response;
    }

    public function show(Guardian $guardian): View
    {
        $this->authorize('view', $guardian);
        $guardian->load(['user', 'students.user', 'students.class']);

        return view('dashboard.guardians.show', compact('guardian'));
    }

    public function edit(Guardian $guardian): View
    {
        $this->authorize('update', $guardian);
        $guardian->load(['user', 'students']);

        return view('dashboard.guardians.edit', [
            'guardian' => $guardian,
        ]);
    }

    public function update(Request $request, Guardian $guardian): RedirectResponse|JsonResponse
    {
        $response = app(GuardianController::class)->update($request, $guardian);
        if ($response instanceof JsonResponse && $response->getStatusCode() === 200) {
            return redirect()->route('dashboard.parents.show', $guardian)->with('status', __('Parent / guardian updated.'));
        }

        return $response;
    }

    public function destroy(Request $request, Guardian $guardian): RedirectResponse|JsonResponse
    {
        $response = app(GuardianController::class)->destroy($guardian);
        if ($response instanceof JsonResponse && $response->getStatusCode() === 200) {
            return redirect()->route('dashboard.parents')->with('status', __('Parent / guardian removed.'));
        }
        if ($response instanceof JsonResponse && $response->getStatusCode() === 422) {
            $msg = DashboardWebHelper::jsonErrorMessage($response) ?? __('Cannot remove this record.');

            return back()->withErrors(['delete' => $msg]);
        }

        return $response;
    }
}
