<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Models\Page;
use App\Models\Plan;
use App\Models\Post;
use App\Models\Settings;
use App\Services\I18n;
use App\Services\Mailer;

class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('site.home', [
            'appPlans'    => Plan::activeFor('app'),
            'themePlans'  => Plan::activeFor('theme'),
            'phpPlans'    => Plan::activeFor('php'),
            'recentPosts' => Post::latest(3),
            'cmsPage'     => $this->cmsPage('/'),
        ]);
    }

    public function product(string $slug): void
    {
        $products = ['app', 'theme', 'php', 'node'];
        if (!in_array($slug, $products, true)) {
            $this->view('errors.404');

            return;
        }

        $this->view('site.products.' . $slug, [
            'plans'   => Plan::activeFor($slug === 'node' ? 'app' : $slug),
            'cmsPage' => $this->cmsPage('/products/' . $slug),
        ]);
    }

    public function pricing(): void
    {
        $this->view('site.pricing', [
            'appPlans'   => Plan::activeFor('app'),
            'themePlans' => Plan::activeFor('theme'),
            'phpPlans'   => Plan::activeFor('php'),
            'cmsPage'    => $this->cmsPage('/pricing'),
        ]);
    }

    public function features(): void
    {
        $this->view('site.features', [
            'cmsPage' => $this->cmsPage('/features'),
        ]);
    }

    public function compare(): void
    {
        $this->view('site.compare', [
            'cmsPage' => $this->cmsPage('/compare'),
        ]);
    }

    public function about(): void
    {
        $this->view('site.about', [
            'cmsPage' => $this->cmsPage('/about'),
        ]);
    }

    public function contact(): void
    {
        $this->view('site.contact', [
            'page'    => 'contact',
            'cmsPage' => $this->cmsPage('/contact'),
        ]);
    }

    /** Load the admin-managed CMS row for a public route (localized, or null). */
    private function cmsPage(string $path): ?array
    {
        $row = Page::forPath($path);

        return $row !== null ? Page::localized($row, I18n::current()) : null;
    }

    public function storeMessage(): void
    {
        $data = $this->validate([
            'name'    => 'required|max:120',
            'email'   => 'required|email|max:191',
            'message' => 'required|max:4000',
        ]);

        $topic = trim((string) ($_POST['topic'] ?? ''));
        $subject = trim((string) ($data['subject'] ?? ''));
        if ($topic !== '') {
            $subject = '[' . $topic . '] ' . $subject;
        }

        \App\Core\Database::getInstance()->insert('contact_messages', [
            'name'       => $data['name'],
            'email'      => $data['email'],
            'subject'    => $subject !== '' ? mb_substr($subject, 0, 191) : null,
            'message'    => $data['message'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->withSuccess(__('contact.success'));

        try {
            // Route sales enquiries to the sales inbox; everything else to support.
            $fallback = (string) Settings::get('site.contact_email', 'support@eskoofy.com');
            $owner = strcasecmp($topic, 'Sales') === 0
                ? (string) Settings::get('site.sales_email', $fallback)
                : (string) Settings::get('site.support_email', $fallback);
            if ($owner === '') {
                $owner = $fallback;
            }
            if ($owner !== '') {
                Mailer::sendView($owner, 'New message from the Eskoofy contact form', 'contact_message', [
                    'name'    => $data['name'],
                    'email'   => $data['email'],
                    'subject' => $subject,
                    'topic'   => $topic,
                    'message' => $data['message'],
                ]);
            }
        } catch (\Throwable) {
            // Best-effort; the page still succeeds.
        }

        $this->redirect('/contact');
    }
}
