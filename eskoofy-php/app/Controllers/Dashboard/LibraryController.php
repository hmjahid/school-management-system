<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class LibraryController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $search = $_GET['search'] ?? '';
        $categoryId = (int) ($_GET['category_id'] ?? 0);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];
        if ($search !== '') {
            $where .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if ($categoryId > 0) {
            $where .= ' AND b.category_id = ?';
            $params[] = $categoryId;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM books b WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT b.*, bc.name as category_name
             FROM books b
             LEFT JOIN book_categories bc ON b.category_id = bc.id
             WHERE {$where}
             ORDER BY b.title ASC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $categories = $this->db->fetchAll("SELECT id, name FROM book_categories ORDER BY name ASC");

        $this->view('dashboard.library.index', [
            'rows'       => $rows,
            'books' => $rows,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'lastPage'   => max(1, (int) ceil($total / $perPage)),
            'search'     => $search,
            'categoryId' => $categoryId,
            'categories' => $categories,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'title'         => 'required|max:255',
            'author'        => 'max:255',
            'publisher'     => 'max:255',
            'isbn'          => 'max:50',
            'category_id'   => 'numeric',
            'shelf_location'=> 'max:255',
            'quantity'      => 'required|numeric',
            'purchase_date' => '',
            'price'         => 'numeric',
            'description'   => 'max:2000',
            'status'        => 'numeric',
        ]);

        $this->db->insert('books', [
            'title'             => $data['title'],
            'author'            => $data['author'] ?? null,
            'publisher'         => $data['publisher'] ?? null,
            'isbn'              => $data['isbn'] ?? null,
            'category_id'       => $data['category_id'] ?? null,
            'shelf_location'    => $data['shelf_location'] ?? null,
            'quantity'          => $data['quantity'],
            'available_quantity'=> $data['quantity'],
            'purchase_date'     => $data['purchase_date'] ?? null,
            'price'             => $data['price'] ?? null,
            'description'       => $data['description'] ?? null,
            'status'            => $data['status'] ?? 1,
            'created_by'        => Auth::id(),
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        Session::getInstance()->flash('success', 'Book added.');
        $this->redirect('/dashboard/books');
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        $book = $this->db->fetch("SELECT * FROM books WHERE id = ? LIMIT 1", [$id]);
        if (!$book) {
            Session::getInstance()->flash('error', 'Book not found.');
            $this->redirect('/dashboard/books');
            return;
        }

        $data = $this->validate([
            'title'         => 'required|max:255',
            'author'        => 'max:255',
            'publisher'     => 'max:255',
            'isbn'          => 'max:50',
            'category_id'   => 'numeric',
            'shelf_location'=> 'max:255',
            'quantity'      => 'required|numeric',
            'purchase_date' => '',
            'price'         => 'numeric',
            'description'   => 'max:2000',
            'status'        => 'numeric',
        ]);

        $diff = $data['quantity'] - $book['quantity'];
        $available = $book['available_quantity'] + $diff;
        if ($available < 0) {
            $available = 0;
        }

        $this->db->update('books', [
            'title'             => $data['title'],
            'author'            => $data['author'] ?? null,
            'publisher'         => $data['publisher'] ?? null,
            'isbn'              => $data['isbn'] ?? null,
            'category_id'       => $data['category_id'] ?? null,
            'shelf_location'    => $data['shelf_location'] ?? null,
            'quantity'          => $data['quantity'],
            'available_quantity'=> $available,
            'purchase_date'     => $data['purchase_date'] ?? null,
            'price'             => $data['price'] ?? null,
            'description'       => $data['description'] ?? null,
            'status'            => $data['status'] ?? 1,
            'updated_at'        => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Book updated.');
        $this->redirect('/dashboard/books');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        $activeIssues = $this->db->count('book_issues', "book_id = ? AND status != 'returned'", [$id]);
        if ($activeIssues > 0) {
            Session::getInstance()->flash('error', 'Cannot delete book with active issues.');
            $this->redirect('/dashboard/books');
            return;
        }

        $this->db->delete('book_issues', 'book_id = ?', [$id]);
        $this->db->delete('books', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Book deleted.');
        $this->redirect('/dashboard/books');
    }

    public function issues(): void
    {
        Auth::requireAuth();
        $status = $_GET['status'] ?? '';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $params = [];
        if ($status !== '') {
            $where .= ' AND bi.status = ?';
            $params[] = $status;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM book_issues bi WHERE {$where}", $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT bi.*, b.title as book_title,
                COALESCE(su.name, tu.name) as member_name
             FROM book_issues bi
             LEFT JOIN books b ON bi.book_id = b.id
             LEFT JOIN students st ON bi.student_id = st.id
             LEFT JOIN users su ON st.user_id = su.id
             LEFT JOIN teachers te ON bi.teacher_id = te.id
             LEFT JOIN users tu ON te.user_id = tu.id
             WHERE {$where}
             ORDER BY bi.issue_date DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        $books = $this->db->fetchAll(
            "SELECT id, title FROM books WHERE available_quantity > 0 ORDER BY title ASC"
        );
        $students = $this->db->fetchAll(
            "SELECT s.id, u.name FROM students s JOIN users u ON s.user_id = u.id WHERE s.status = 'active' ORDER BY u.name ASC LIMIT 500"
        );

        $this->view('dashboard.library.issues', [
            'rows'      => $rows,
            'total'     => $total,
            'page'      => $page,
            'perPage'   => $perPage,
            'lastPage'  => max(1, (int) ceil($total / $perPage)),
            'status'    => $status,
            'books'     => $books,
            'students'  => $students,
        ]);
    }

    public function showIssue(int $id): void
    {
        Auth::requireAuth();
        $issue = $this->db->fetch(
            "SELECT bi.*, b.title as book_title,
                COALESCE(su.name, tu.name) as member_name
             FROM book_issues bi
             LEFT JOIN books b ON bi.book_id = b.id
             LEFT JOIN students st ON bi.student_id = st.id
             LEFT JOIN users su ON st.user_id = su.id
             LEFT JOIN teachers te ON bi.teacher_id = te.id
             LEFT JOIN users tu ON te.user_id = tu.id
             WHERE bi.id = ? LIMIT 1",
            [$id]
        );
        if (!$issue) {
            Session::getInstance()->flash('error', 'Issue record not found.');
            $this->redirect('/dashboard/book-issues');
            return;
        }

        $this->view('dashboard.library.issue_show', ['issue' => $issue]);
    }

    public function issueBook(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'book_id'    => 'required|numeric',
            'student_id' => 'required|numeric',
            'due_date'   => 'required',
            'notes'      => 'max:500',
        ]);

        $book = $this->db->fetch("SELECT * FROM books WHERE id = ? LIMIT 1", [$data['book_id']]);
        if (!$book || $book['available_quantity'] <= 0) {
            Session::getInstance()->flash('error', 'Book is not available.');
            $this->back();
            return;
        }

        $this->db->insert('book_issues', [
            'book_id'    => $data['book_id'],
            'student_id' => $data['student_id'],
            'issued_by'  => Auth::id(),
            'issue_date' => date('Y-m-d'),
            'due_date'   => $data['due_date'],
            'notes'      => $data['notes'] ?? null,
            'status'     => 'issued',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db->update('books', [
            'available_quantity' => ((int) $book['available_quantity']) - 1,
            'updated_at'         => date('Y-m-d H:i:s'),
        ], 'id = ?', [$data['book_id']]);

        Session::getInstance()->flash('success', 'Book issued successfully.');
        $this->redirect('/dashboard/book-issues');
    }

    public function returnBook(int $id): void
    {
        Auth::requireAuth();
        $issue = $this->db->fetch("SELECT * FROM book_issues WHERE id = ? LIMIT 1", [$id]);
        if (!$issue) {
            Session::getInstance()->flash('error', 'Issue record not found.');
            $this->redirect('/dashboard/book-issues');
            return;
        }

        $this->applyReturn($issue);
        Session::getInstance()->flash('success', 'Book returned successfully.');
        $this->redirect('/dashboard/book-issues');
    }

    public function applyReturn(array $issue): void
    {
        $settings = $this->db->fetch("SELECT * FROM library_settings ORDER BY id DESC LIMIT 1");
        $lateFeePerDay = (float) ($settings['late_fee_per_day'] ?? 5.00);

        $lateFee = 0.0;
        if ($issue['due_date'] && $issue['due_date'] < date('Y-m-d')) {
            $days = (int) ((strtotime(date('Y-m-d')) - strtotime($issue['due_date'])) / 86400);
            $lateFee = round($days * $lateFeePerDay, 2);
        }

        $this->db->update('book_issues', [
            'status'      => 'returned',
            'return_date' => date('Y-m-d'),
            'late_fee'    => $lateFee,
            'updated_at'  => date('Y-m-d H:i:s'),
        ], 'id = ?', [$issue['id']]);

        $book = $this->db->fetch("SELECT * FROM books WHERE id = ? LIMIT 1", [$issue['book_id']]);
        if ($book) {
            $this->db->update('books', [
                'available_quantity' => ((int) $book['available_quantity']) + 1,
                'updated_at'         => date('Y-m-d H:i:s'),
            ], 'id = ?', [$issue['book_id']]);
        }
    }

    public function collectFine(int $id): void
    {
        Auth::requireAuth();
        $issue = $this->db->fetch("SELECT * FROM book_issues WHERE id = ? LIMIT 1", [$id]);
        if (!$issue) {
            Session::getInstance()->flash('error', 'Issue record not found.');
            $this->redirect('/dashboard/book-issues');
            return;
        }
        if ($issue['status'] !== 'returned') {
            Session::getInstance()->flash('error', 'Can only collect fine for returned books.');
            $this->redirect('/dashboard/book-issues/' . $id);
            return;
        }

        $this->applyFine($id);

        Session::getInstance()->flash('success', 'Fine collected.');
        $this->redirect('/dashboard/book-issues/' . $id);
    }

    public function applyFine(int $id): void
    {
        $this->db->update('book_issues', [
            'fine_paid'  => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
    }

    public function markLost(int $id): void
    {
        Auth::requireAuth();
        $issue = $this->db->fetch("SELECT * FROM book_issues WHERE id = ? LIMIT 1", [$id]);
        if (!$issue) {
            Session::getInstance()->flash('error', 'Issue record not found.');
            $this->redirect('/dashboard/book-issues');
            return;
        }
        if ($issue['status'] !== 'issued') {
            Session::getInstance()->flash('error', 'Only issued books can be marked as lost.');
            $this->redirect('/dashboard/book-issues/' . $id);
            return;
        }

        $this->db->update('book_issues', [
            'status'     => 'lost',
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Book marked as lost.');
        $this->redirect('/dashboard/book-issues/' . $id);
    }
}
