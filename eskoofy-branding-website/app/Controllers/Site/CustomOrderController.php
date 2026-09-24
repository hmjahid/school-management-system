<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Database;
use App\Models\CustomRequest;
use App\Models\Page;
use App\Services\I18n;
use App\Services\Mailer;
use App\Models\Settings;

class CustomOrderController extends Controller
{
    public function show(): void
    {
        $product = (string) ($_GET['product'] ?? '');
        if ($product !== '' && !in_array($product, CustomRequest::PRODUCTS, true)) {
            $product = '';
        }

        $this->view('site.custom-order', [
            'cmsPage'   => $this->cmsPage('/custom-order'),
            'products'  => CustomRequest::PRODUCTS,
            'types'     => CustomRequest::TYPES,
            'product'   => $product,
            'ranges'    => ['under_250', '250_1000', '1000_3000', '3000_plus'],
            'timelines' => ['asap', '1_2_weeks', '2_4_weeks', 'a_month_plus', 'flexible'],
        ]);
    }

    public function store(): void
    {
        $data = $this->validate([
            'name'         => 'required|max:191',
            'email'        => 'required|email|max:191',
            'phone'        => 'nullable|max:64',
            'product'      => 'required|in:' . implode(',', CustomRequest::PRODUCTS),
            'request_type' => 'required|in:' . implode(',', CustomRequest::TYPES),
            'subject'      => 'nullable|max:191',
            'details'      => 'required|max:4000',
            'budget'       => 'nullable|in:under_250,250_1000,1000_3000,3000_plus',
            'timeline'     => 'nullable|max:191',
        ]);

        $id = Database::getInstance()->insert('custom_requests', [
            'name'         => $data['name'],
            'email'        => $data['email'],
            'phone'        => !empty($data['phone']) ? $data['phone'] : null,
            'product'      => $data['product'],
            'request_type' => $data['request_type'],
            'subject'      => !empty($data['subject']) ? mb_substr($data['subject'], 0, 191) : null,
            'details'      => $data['details'],
            'budget'       => !empty($data['budget']) ? $data['budget'] : null,
            'timeline'     => !empty($data['timeline']) ? mb_substr($data['timeline'], 0, 191) : null,
            'status'       => 'new',
            'read_at'      => null,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->withSuccess(__('custom_order.success'));

        try {
            $salesEmail = (string) Settings::get('site.sales_email', 'sales@eskoofy.com');
            if ($salesEmail !== '') {
                Mailer::sendView($salesEmail, 'New custom order request — ' . $data['product'], 'custom_request', [
                    'id'           => $id,
                    'name'         => $data['name'],
                    'email'        => $data['email'],
                    'phone'        => $data['phone'] ?? '',
                    'product'      => $data['product'],
                    'request_type' => $data['request_type'],
                    'subject'      => $data['subject'] ?? '',
                    'details'      => $data['details'],
                    'budget'       => $data['budget'] ?? '',
                    'timeline'     => $data['timeline'] ?? '',
                ]);
            }
        } catch (\Throwable) {
            // Best-effort; the request is already stored.
        }

        $this->redirect('/custom-order');
    }

    private function cmsPage(string $path): ?array
    {
        $row = Page::forPath($path);

        return $row !== null ? Page::localized($row, I18n::current()) : null;
    }
}