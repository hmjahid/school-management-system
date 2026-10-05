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
use App\Services\ProductMatrix;

class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('site.home', [
            'appPlans'    => Plan::activeFor('app'),
            'themePlans'  => Plan::activeFor('theme'),
            'phpPlans'    => Plan::activeFor('php'),
            'nodePlans'   => Plan::activeFor('node'),
            'matrix'      => ProductMatrix::build(),
            'recentPosts' => Post::latest(3),
            'cmsPage'     => $this->cmsPage('/'),
        ]);
    }

    /**
     * `/products` — the canonical Product x Variant explainer.
     *
     * This is the page the whole taxonomy hangs off: every product page links
     * back here as its breadcrumb, and it is the only place that shows all
     * eight product x variant combinations at once, including the explicit
     * "not offered yet" state.
     */
    public function products(): void
    {
        $this->view('site.products_index', [
            'matrix'  => ProductMatrix::build(),
            'cmsPage' => $this->cmsPage('/products'),
        ]);
    }

    public function product(string $slug): void
    {
        if (!\App\Services\Catalog::has($slug)) {
            // The slug route matches any string, so the status code has to be
            // set here — otherwise an unknown product returns the 404 page with
            // a 200 and search engines index it.
            http_response_code(404);
            $this->view('errors.404');

            return;
        }

        $variant = \App\Services\VariantResolver::normalize($_GET['variant'] ?? null);

        $this->view('site.products.' . strtolower($slug), [
            // Plans are per product and carry no variant (one USD price list per
            // product, two market profiles), so the product slug is the key.
            // The Node.js product has its own plans — it must not borrow the
            // app's, or the matrix and this page would disagree on its price.
            'plans'   => Plan::activeFor($slug),
            'matrix'  => ProductMatrix::build(),
            // Named `$productVariant`, not `$variant`: `View::share('variant', …)`
            // already publishes the site's own build profile, and shadowing it
            // would silently change the layout's language/currency context.
            'productVariant' => $variant,
            'productCode'    => $slug,
            'productPath'    => \App\Services\Catalog::page($slug),
            'cmsPage' => $this->cmsPage('/products/' . $slug),
        ]);
    }

    public function pricing(): void
    {
        $this->view('site.pricing', [
            'appPlans'   => Plan::activeFor('app'),
            'themePlans' => Plan::activeFor('theme'),
            'phpPlans'   => Plan::activeFor('php'),
            'nodePlans'  => Plan::activeFor('node'),
            'matrix'     => ProductMatrix::build(),
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
            'matrix'  => ProductMatrix::build(),
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
