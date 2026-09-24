<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Models\Page;
use App\Services\I18n;

class LegalController extends Controller
{
    public function refundPolicy(): void
    {
        $this->legal('refund', '/refund-policy');
    }

    public function terms(): void
    {
        $this->legal('terms', '/terms');
    }

    public function privacy(): void
    {
        $this->legal('privacy', '/privacy');
    }

    private function legal(string $page, string $canonical): void
    {
        $this->view('site.legal', [
            'page'      => $page,
            'title'     => __('legal.' . $page . '.title'),
            'canonical' => $canonical,
            'cmsPage'   => $this->cmsPage($canonical),
        ]);
    }

    /** Load the admin-managed CMS row for a public route (localized, or null). */
    private function cmsPage(string $path): ?array
    {
        $row = Page::forPath($path);

        return $row !== null ? Page::localized($row, I18n::current()) : null;
    }
}