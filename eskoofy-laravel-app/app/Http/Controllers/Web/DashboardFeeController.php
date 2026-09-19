<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Api\FeeController as ApiFeeController;
use App\Http\Controllers\Controller;
use App\Models\Fee;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class DashboardFeeController extends Controller
{
    protected function toSymfonyResponse(Request $request, mixed $response): Response
    {
        if ($response instanceof Responsable) {
            return $response->toResponse($request);
        }

        return $response instanceof Response ? $response : new Response('', 500);
    }

    protected function authorizeFees(Request $request): void
    {
        $user = $request->user();
        $allowed = $user->hasAnyRole(['admin', 'accountant'])
            || $user->hasAnyPermission(['collect_fees', 'view_financial_reports', 'manage_fee_categories', 'manage_fee_types']);

        abort_unless($allowed, 403);
    }

    public function create(Request $request): View
    {
        $this->authorizeFees($request);

        return view('dashboard.fees.create', [
            'classes' => SchoolClass::orderBy('name')->get(),
            'sections' => Section::orderBy('name')->limit(300)->get(),
            'students' => Student::with('user')->orderByDesc('id')->limit(200)->get(),
            'feeTypes' => Fee::getFeeTypes(),
            'frequencies' => Fee::getFrequencies(),
        ]);
    }

    public function store(Request $request): RedirectResponse|Response
    {
        $this->authorizeFees($request);

        $sym = $this->toSymfonyResponse($request, app(ApiFeeController::class)->store($request));
        if ($sym->getStatusCode() === 200) {
            return redirect()->route('dashboard.fees')->with('status', __('Fee created.'));
        }

        return $sym;
    }

    public function edit(Request $request, Fee $fee): View
    {
        $this->authorizeFees($request);
        $fee->load(['schoolClass', 'section', 'student.user']);

        return view('dashboard.fees.edit', [
            'fee' => $fee,
            'classes' => SchoolClass::orderBy('name')->get(),
            'sections' => Section::orderBy('name')->limit(300)->get(),
            'students' => Student::with('user')->orderByDesc('id')->limit(200)->get(),
            'feeTypes' => Fee::getFeeTypes(),
            'frequencies' => Fee::getFrequencies(),
        ]);
    }

    public function update(Request $request, Fee $fee): RedirectResponse|Response
    {
        $this->authorizeFees($request);

        $sym = $this->toSymfonyResponse($request, app(ApiFeeController::class)->update($request, $fee));
        if ($sym->getStatusCode() === 200) {
            return redirect()->route('dashboard.fees')->with('status', __('Fee updated.'));
        }

        return $sym;
    }

    public function destroy(Request $request, Fee $fee): RedirectResponse|Response
    {
        $this->authorizeFees($request);

        $raw = app(ApiFeeController::class)->destroy($fee);
        $sym = $this->toSymfonyResponse($request, $raw);
        if ($sym->getStatusCode() === 204) {
            return redirect()->route('dashboard.fees')->with('status', __('Fee removed.'));
        }
        if ($sym instanceof JsonResponse && $sym->getStatusCode() === 422) {
            $msg = DashboardWebHelper::jsonErrorMessage($sym) ?? __('Cannot delete this fee.');

            return back()->withErrors(['delete' => $msg]);
        }

        return $sym;
    }
}
