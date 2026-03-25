<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use App\Models\Teacher;
use App\Models\WebsiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SitePageController extends Controller
{
    public function about(): View
    {
        return $this->renderCmsPage('about');
    }

    public function academics(): View
    {
        return $this->renderCmsPage('academics');
    }

    public function admissions(): View
    {
        $content = WebsiteContent::getContent('admissions');

        return view('site.admissions', ['content' => $content]);
    }

    public function students(): View
    {
        return $this->renderCmsPage('students');
    }

    public function faculty(): View
    {
        $content = WebsiteContent::getContent('faculty');
        $teachers = Teacher::query()
            ->where('status', 'active')
            ->with('user')
            ->orderBy('joining_date', 'desc')
            ->limit(80)
            ->get();

        return view('site.faculty', [
            'content' => $content,
            'teachers' => $teachers,
        ]);
    }

    public function contact(): View
    {
        $content = WebsiteContent::getContent('contact');

        return view('site.contact', ['content' => $content]);
    }

    public function terms(): View
    {
        return $this->renderCmsPage('terms');
    }

    public function privacy(): View
    {
        return $this->renderCmsPage('privacy');
    }

    public function contactStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:100',
            'phone' => 'nullable|string|max:30',
            'subject' => 'nullable|string|max:200',
            'message' => 'required|string|max:5000',
            'website' => 'nullable|max:0',
        ]);

        ContactSubmission::create([
            'type' => ContactSubmission::TYPE_CONTACT,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'subject' => $validated['subject'] ?? null,
            'message' => $validated['message'],
        ]);

        return redirect()->route('site.contact')->with('status', __('Thank you — we will get back to you soon.'));
    }

    public function feedbackStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:100',
            'message' => 'required|string|max:5000',
            'website' => 'nullable|max:0',
        ]);

        ContactSubmission::create([
            'type' => ContactSubmission::TYPE_FEEDBACK,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'subject' => __('Feedback'),
            'message' => $validated['message'],
        ]);

        return redirect()->route('site.contact')->with('status', __('Thank you for your feedback.'));
    }

    public function complaintStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:100',
            'phone' => 'nullable|string|max:30',
            'message' => 'required|string|max:5000',
            'website' => 'nullable|max:0',
        ]);

        ContactSubmission::create([
            'type' => ContactSubmission::TYPE_COMPLAINT,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'subject' => __('Complaint'),
            'message' => $validated['message'],
        ]);

        return redirect()->route('site.contact')->with('status', __('Your complaint has been recorded. We will follow up shortly.'));
    }

    public function newsletterStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:100',
        ]);

        ContactSubmission::create([
            'type' => ContactSubmission::TYPE_NEWSLETTER,
            'name' => __('Newsletter subscriber'),
            'email' => $validated['email'],
            'subject' => __('Newsletter subscription'),
            'message' => __('Please add this email to the school newsletter list.'),
        ]);

        return back()->with('status', __('Thanks — you are subscribed to updates.'));
    }

    public function scholarshipStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:100',
            'phone' => 'nullable|string|max:30',
            'message' => 'required|string|max:5000',
            'website' => 'nullable|max:0',
        ]);

        ContactSubmission::create([
            'type' => ContactSubmission::TYPE_SCHOLARSHIP,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'subject' => __('Scholarship application'),
            'message' => $validated['message'],
        ]);

        return redirect()->route('site.admissions')->with('status', __('Scholarship request received. Our office will contact you.'));
    }

    protected function renderCmsPage(string $slug): View
    {
        $content = WebsiteContent::getContent($slug);

        return view('site.page', ['slug' => $slug, 'content' => $content]);
    }
}
