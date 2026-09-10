<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Models\Plan;
use App\Models\Post;

class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('site.home', [
            'appPlans'    => Plan::activeFor('app'),
            'themePlans'  => Plan::activeFor('theme'),
            'recentPosts' => Post::latest(3),
        ]);
    }

    public function product(string $slug): void
    {
        $products = ['app', 'theme'];
        if (!in_array($slug, $products, true)) {
            $this->view('errors.404');

            return;
        }

        $this->view('site.products.' . $slug, [
            'plans' => Plan::activeFor($slug),
        ]);
    }

    public function pricing(): void
    {
        $this->view('site.pricing', [
            'appPlans'   => Plan::activeFor('app'),
            'themePlans' => Plan::activeFor('theme'),
        ]);
    }

    public function features(): void
    {
        $this->view('site.features');
    }

    public function about(): void
    {
        $this->view('site.about');
    }

    public function contact(): void
    {
        $this->view('site.contact', ['page' => 'contact']);
    }

    public function storeMessage(): void
    {
        $data = $this->validate([
            'name'    => 'required|max:120',
            'email'   => 'required|email|max:191',
            'message' => 'required|max:4000',
        ]);

        \App\Core\Database::getInstance()->insert('contact_messages', [
            'name'       => $data['name'],
            'email'      => $data['email'],
            'subject'    => $data['subject'] ?? null,
            'message'    => $data['message'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->withSuccess(__('contact.success'));
        $this->redirect('/contact');
    }
}
