<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class TestimonialController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $rows = $this->db->fetchAll(
            "SELECT * FROM testimonials ORDER BY sort_order ASC, id DESC"
        );

        $this->view('dashboard.testimonials.index', ['rows' => $rows]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'name'        => 'required|max:255',
            'content'     => 'required',
            'role'        => 'max:100',
            'school'      => 'max:255',
            'rating'      => 'numeric|min:1|max:5',
            'is_active'   => 'numeric',
            'sort_order'  => 'numeric',
        ]);

        $this->db->insert('testimonials', [
            'name'        => $data['name'],
            'content'     => $data['content'],
            'role'        => $data['role'] ?? null,
            'school'      => $data['school'] ?? null,
            'rating'      => $data['rating'] ?? 5,
            'is_active'   => $data['is_active'] ?? 1,
            'sort_order'  => $data['sort_order'] ?? 0,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Testimonial added.');
        $this->redirect('/dashboard/testimonials');
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $testimonial = $this->db->fetch("SELECT * FROM testimonials WHERE id = ? LIMIT 1", [$id]);
        if (!$testimonial) {
            Session::getInstance()->flash('error', 'Testimonial not found.');
            $this->redirect('/dashboard/testimonials');
            return;
        }

        $data = $this->validate([
            'name'        => 'required|max:255',
            'content'     => 'required',
            'role'        => 'max:100',
            'school'      => 'max:255',
            'rating'      => 'numeric|min:1|max:5',
            'is_active'   => 'numeric',
            'sort_order'  => 'numeric',
        ]);

        $this->db->update('testimonials', [
            'name'        => $data['name'],
            'content'     => $data['content'],
            'role'        => $data['role'] ?? null,
            'school'      => $data['school'] ?? null,
            'rating'      => $data['rating'] ?? 5,
            'is_active'   => $data['is_active'] ?? 1,
            'sort_order'  => $data['sort_order'] ?? 0,
            'updated_at'  => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Testimonial updated.');
        $this->redirect('/dashboard/testimonials');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $this->db->delete('testimonials', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Testimonial deleted.');
        $this->redirect('/dashboard/testimonials');
    }
}
