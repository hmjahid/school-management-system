<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;
use App\Models\Book;
use App\Models\BookCategory;
use App\Models\BookIssue;
use App\Models\LibrarySetting;
use App\Models\Student;
use App\Models\Teacher;

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

    public function bookIndex(): void
    {
        Auth::requireAuth();
        $query = Book::query()->whereNull('deleted_at');

        $search = request('search', '');
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->whereRaw('(title LIKE ? OR author LIKE ? OR isbn LIKE ?)', [$like, $like, $like]);
        }

        $categoryId = (int) request('category_id', 0);
        if ($categoryId > 0) {
            $query->where('category_id', $categoryId);
        }

        $status = request('status', '');
        if ($status === '0' || $status === '1') {
            $query->where('status', (int) $status);
        }

        $query->latest();

        $page = max(1, (int) request('page', 1));
        $perPage = 15;

        $books = $this->paginateRows($query->get(), $query->count(), $perPage, $page);

        $this->view('dashboard.library.books.index', [
            'books'      => $books,
            'categories' => BookCategory::query()->orderBy('name', 'asc')->get(),
        ]);
    }

    public function bookCreate(): void
    {
        Auth::requireAuth();
        $this->view('dashboard.library.books.create', [
            'categories' => BookCategory::query()->orderBy('name', 'asc')->get(),
        ]);
    }

    public function bookStore(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'title'          => 'required|max:255',
            'author'         => 'max:255',
            'publisher'      => 'max:255',
            'isbn'           => 'max:50',
            'category_id'    => 'numeric',
            'shelf_location' => 'max:255',
            'quantity'       => 'required|numeric',
            'purchase_date'  => 'date',
            'price'          => 'numeric',
            'description'    => 'max:2000',
            'status'         => 'numeric',
        ]);

        Book::create([
            'title'              => $data['title'],
            'author'             => $data['author'] ?? null,
            'publisher'          => $data['publisher'] ?? null,
            'isbn'               => $data['isbn'] ?? null,
            'category_id'        => ((int) ($data['category_id'] ?? 0)) ?: null,
            'shelf_location'     => $data['shelf_location'] ?? null,
            'quantity'           => (int) $data['quantity'],
            'available_quantity' => (int) $data['quantity'],
            'purchase_date'      => $data['purchase_date'] ?? null,
            'price'              => $data['price'] ?? null,
            'description'        => $data['description'] ?? null,
            'cover_image'        => $this->storeCover(),
            'status'             => isset($data['status']) ? 1 : 0,
            'created_by'         => Auth::id(),
        ]);

        Session::getInstance()->flash('success', 'Book added successfully.');
        $this->redirect(route('dashboard.library.books.index'));
    }

    public function bookShow(int $id): void
    {
        Auth::requireAuth();
        $book = Book::find($id);
        if ($book === null) {
            Session::getInstance()->flash('error', 'Book not found.');
            $this->redirect(route('dashboard.library.books.index'));
            return;
        }

        $this->view('dashboard.library.books.show', ['book' => $book]);
    }

    public function bookEdit(int $id): void
    {
        Auth::requireAuth();
        $book = Book::find($id);
        if ($book === null) {
            Session::getInstance()->flash('error', 'Book not found.');
            $this->redirect(route('dashboard.library.books.index'));
            return;
        }

        $this->view('dashboard.library.books.edit', [
            'book'       => $book,
            'categories' => BookCategory::query()->orderBy('name', 'asc')->get(),
        ]);
    }

    public function bookUpdate(int $id): void
    {
        Auth::requireAuth();
        $book = Book::find($id);
        if ($book === null) {
            Session::getInstance()->flash('error', 'Book not found.');
            $this->redirect(route('dashboard.library.books.index'));
            return;
        }

        $data = $this->validate([
            'title'          => 'required|max:255',
            'author'         => 'max:255',
            'publisher'      => 'max:255',
            'isbn'           => 'max:50',
            'category_id'    => 'numeric',
            'shelf_location' => 'max:255',
            'quantity'       => 'required|numeric',
            'purchase_date'  => 'date',
            'price'          => 'numeric',
            'description'    => 'max:2000',
            'status'         => 'numeric',
        ]);

        $diff = (int) $data['quantity'] - (int) $book->quantity;
        $available = max(0, (int) $book->available_quantity + $diff);

        $fields = [
            'title'              => $data['title'],
            'author'             => $data['author'] ?? null,
            'publisher'          => $data['publisher'] ?? null,
            'isbn'               => $data['isbn'] ?? null,
            'category_id'        => ((int) ($data['category_id'] ?? 0)) ?: null,
            'shelf_location'     => $data['shelf_location'] ?? null,
            'quantity'           => (int) $data['quantity'],
            'available_quantity' => $available,
            'purchase_date'      => $data['purchase_date'] ?? null,
            'price'              => $data['price'] ?? null,
            'description'        => $data['description'] ?? null,
            'status'             => isset($data['status']) ? 1 : 0,
        ];

        $cover = $this->storeCover();
        if ($cover !== null) {
            $fields['cover_image'] = $cover;
        }

        $book->update($fields);

        Session::getInstance()->flash('success', 'Book updated successfully.');
        $this->redirect(route('dashboard.library.books.index'));
    }

    public function bookDestroy(int $id): void
    {
        Auth::requireAuth();
        $activeIssues = BookIssue::query()
            ->where('book_id', $id)
            ->where('status', '!=', BookIssue::STATUS_RETURNED)
            ->count();
        if ($activeIssues > 0) {
            Session::getInstance()->flash('error', 'Cannot delete book with active issues.');
            $this->redirect(route('dashboard.library.books.index'));
            return;
        }

        $this->db->delete('book_issues', 'book_id = ?', [$id]);
        $this->db->delete('books', 'id = ?', [$id]);
        Session::getInstance()->flash('success', 'Book deleted.');
        $this->redirect(route('dashboard.library.books.index'));
    }

    public function categories(): void
    {
        Auth::requireAuth();

        $categories = BookCategory::query()->orderBy('name', 'asc')->get();

        $rows = $this->db->fetchAll(
            'SELECT category_id, COUNT(*) as cnt FROM books WHERE category_id IS NOT NULL AND deleted_at IS NULL GROUP BY category_id'
        );
        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['category_id']] = (int) $row['cnt'];
        }
        foreach ($categories as $category) {
            $category->setAttribute('books_count', $counts[(int) $category->id] ?? 0);
        }

        $this->view('dashboard.library.categories.index', ['categories' => $categories]);
    }

    public function categoryStore(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'name'        => 'required|max:255',
            'description' => 'max:1000',
        ]);

        if (BookCategory::query()->where('name', $data['name'])->exists()) {
            Session::getInstance()->flash('error', 'A category with this name already exists.');
            $this->back();
            return;
        }

        BookCategory::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        Session::getInstance()->flash('success', 'Category created.');
        $this->redirect(route('dashboard.library.categories.index'));
    }

    public function categoryUpdate(int $id): void
    {
        Auth::requireAuth();
        $category = BookCategory::find($id);
        if ($category === null) {
            Session::getInstance()->flash('error', 'Category not found.');
            $this->redirect(route('dashboard.library.categories.index'));
            return;
        }

        $data = $this->validate([
            'name'        => 'required|max:255',
            'description' => 'max:1000',
        ]);

        $duplicate = BookCategory::query()
            ->where('name', $data['name'])
            ->where('id', '!=', $id)
            ->exists();
        if ($duplicate) {
            Session::getInstance()->flash('error', 'A category with this name already exists.');
            $this->back();
            return;
        }

        $category->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        Session::getInstance()->flash('success', 'Category updated.');
        $this->redirect(route('dashboard.library.categories.index'));
    }

    public function categoryDestroy(int $id): void
    {
        Auth::requireAuth();
        $bookCount = Book::query()->where('category_id', $id)->whereNull('deleted_at')->count();
        if ($bookCount > 0) {
            Session::getInstance()->flash('error', 'Cannot delete a category that still has books.');
            $this->redirect(route('dashboard.library.categories.index'));
            return;
        }

        BookCategory::find($id)?->delete();
        Session::getInstance()->flash('success', 'Category deleted.');
        $this->redirect(route('dashboard.library.categories.index'));
    }

    public function issueIndex(): void
    {
        Auth::requireAuth();
        $search = request('search', '');
        $status = request('status', '');
        $page = max(1, (int) request('page', 1));
        $perPage = 15;

        $where = 'bi.deleted_at IS NULL';
        $params = [];
        if ($search !== '') {
            $like = '%' . $search . '%';
            $where .= ' AND (b.title LIKE ? OR COALESCE(su.name, tu.name) LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }
        if ($status !== '') {
            $where .= ' AND bi.status = ?';
            $params[] = $status;
        }

        $total = (int) ($this->db->fetch(
            "SELECT COUNT(*) as cnt FROM book_issues bi
             LEFT JOIN books b ON bi.book_id = b.id
             LEFT JOIN students st ON bi.student_id = st.id
             LEFT JOIN users su ON st.user_id = su.id
             LEFT JOIN teachers te ON bi.teacher_id = te.id
             LEFT JOIN users tu ON te.user_id = tu.id
             WHERE {$where}",
            $params
        )['cnt'] ?? 0);

        $rows = $this->db->fetchAll(
            "SELECT bi.* FROM book_issues bi
             LEFT JOIN books b ON bi.book_id = b.id
             LEFT JOIN students st ON bi.student_id = st.id
             LEFT JOIN users su ON st.user_id = su.id
             LEFT JOIN teachers te ON bi.teacher_id = te.id
             LEFT JOIN users tu ON te.user_id = tu.id
             WHERE {$where}
             ORDER BY bi.created_at DESC, bi.id DESC
             LIMIT {$perPage} OFFSET " . (($page - 1) * $perPage),
            $params
        );

        $issues = $this->paginateRows($rows, $total, $perPage, $page, BookIssue::class);

        $this->view('dashboard.library.issues.index', [
            'issues' => $issues,
        ]);
    }

    public function issueCreate(): void
    {
        Auth::requireAuth();

        $books = Book::query()
            ->whereNull('deleted_at')
            ->where('status', 1)
            ->whereRaw('available_quantity > 0')
            ->orderBy('title', 'asc')
            ->get();

        $students = new \App\Core\Support\Collection(Student::query()
            ->orderBy('first_name', 'asc')
            ->limit(500)
            ->get());

        $teachers = new \App\Core\Support\Collection(Teacher::query()
            ->orderBy('id', 'asc')
            ->limit(500)
            ->get()->sortBy(fn ($t) => (string) ($t->user?->name ?? '')));

        $this->view('dashboard.library.issues.create', [
            'books'    => $books,
            'students' => $students,
            'teachers' => $teachers,
            'settings' => LibrarySetting::getSettings(),
        ]);
    }

    public function issueStore(): void
    {
        Auth::requireAuth();
        $data = $this->validate([
            'book_id'    => 'required|numeric',
            'student_id' => 'numeric',
            'teacher_id' => 'numeric',
            'due_date'   => 'required|date',
            'notes'      => 'max:500',
        ]);

        $book = Book::find((int) $data['book_id']);
        if ($book === null || !$book->isAvailable()) {
            Session::getInstance()->flash('error', 'Book is not available.');
            $this->back();
            return;
        }

        $studentId = ($data['student_id'] ?? null) !== '' && ($data['student_id'] ?? null) !== null
            ? (int) $data['student_id'] : null;
        $teacherId = ($data['teacher_id'] ?? null) !== '' && ($data['teacher_id'] ?? null) !== null
            ? (int) $data['teacher_id'] : null;
        if ($studentId === null && $teacherId === null) {
            Session::getInstance()->flash('error', 'Select a student or a teacher.');
            $this->back();
            return;
        }

        $settings = LibrarySetting::getSettings();
        BookIssue::create([
            'book_id'    => (int) $data['book_id'],
            'student_id' => $studentId,
            'teacher_id' => $teacherId,
            'issue_date' => date('Y-m-d'),
            'due_date'   => $data['due_date'],
            'status'     => BookIssue::STATUS_ISSUED,
            'notes'      => $data['notes'] ?? null,
            'issued_by'  => Auth::id(),
            'late_fee'   => null,
            'fine_paid'  => 0,
        ]);

        $book->update([
            'available_quantity' => (int) $book->available_quantity - 1,
        ]);

        Session::getInstance()->flash('success', 'Book issued successfully.');
        $this->redirect(route('dashboard.library.issues.index'));
    }

    public function issueShow(int $id): void
    {
        Auth::requireAuth();
        $issue = BookIssue::find($id);
        if ($issue === null) {
            Session::getInstance()->flash('error', 'Issue record not found.');
            $this->redirect(route('dashboard.library.issues.index'));
            return;
        }

        $this->view('dashboard.library.issues.show', ['issue' => $issue]);
    }

    public function issueReturn(int $id): void
    {
        Auth::requireAuth();
        $issue = BookIssue::find($id);
        if ($issue === null) {
            Session::getInstance()->flash('error', 'Issue record not found.');
            $this->redirect(route('dashboard.library.issues.index'));
            return;
        }
        if ($issue->status !== BookIssue::STATUS_ISSUED) {
            Session::getInstance()->flash('error', 'Only issued books can be returned.');
            $this->redirect(route('dashboard.library.issues.show', ['issue' => $id]));
            return;
        }

        $lateFee = $issue->calculateLateFee((float) LibrarySetting::getSettings()->late_fee_per_day);

        $issue->update([
            'status'      => BookIssue::STATUS_RETURNED,
            'return_date' => date('Y-m-d'),
            'late_fee'    => $lateFee,
        ]);

        $book = Book::find((int) $issue->book_id);
        if ($book !== null) {
            $book->update([
                'available_quantity' => (int) $book->available_quantity + 1,
            ]);
        }

        Session::getInstance()->flash('success', 'Book returned successfully.');
        $this->redirect(route('dashboard.library.issues.index'));
    }

    public function issueFine(int $id): void
    {
        Auth::requireAuth();
        $issue = BookIssue::find($id);
        if ($issue === null) {
            Session::getInstance()->flash('error', 'Issue record not found.');
            $this->redirect(route('dashboard.library.issues.index'));
            return;
        }
        if ($issue->status !== BookIssue::STATUS_RETURNED) {
            Session::getInstance()->flash('error', 'Can only collect fine for returned books.');
            $this->redirect(route('dashboard.library.issues.show', ['issue' => $id]));
            return;
        }

        $issue->update(['fine_paid' => 1]);

        Session::getInstance()->flash('success', 'Fine collected.');
        $this->redirect(route('dashboard.library.issues.show', ['issue' => $id]));
    }

    public function issueLost(int $id): void
    {
        Auth::requireAuth();
        $issue = BookIssue::find($id);
        if ($issue === null) {
            Session::getInstance()->flash('error', 'Issue record not found.');
            $this->redirect(route('dashboard.library.issues.index'));
            return;
        }
        if ($issue->status !== BookIssue::STATUS_ISSUED) {
            Session::getInstance()->flash('error', 'Only issued books can be marked as lost.');
            $this->redirect(route('dashboard.library.issues.show', ['issue' => $id]));
            return;
        }

        $issue->update(['status' => BookIssue::STATUS_LOST]);

        Session::getInstance()->flash('success', 'Book marked as lost.');
        $this->redirect(route('dashboard.library.issues.show', ['issue' => $id]));
    }

    public function issueDestroy(int $id): void
    {
        Auth::requireAuth();
        $issue = BookIssue::find($id);
        if ($issue === null) {
            Session::getInstance()->flash('error', 'Issue record not found.');
            $this->redirect(route('dashboard.library.issues.index'));
            return;
        }

        if ($issue->status === BookIssue::STATUS_ISSUED) {
            $book = Book::find((int) $issue->book_id);
            if ($book !== null) {
                $book->update([
                    'available_quantity' => (int) $book->available_quantity + 1,
                ]);
            }
        }

        $issue->delete();

        Session::getInstance()->flash('success', 'Issue record deleted.');
        $this->redirect(route('dashboard.library.issues.index'));
    }

    private function storeCover(): ?string
    {
        $file = $_FILES['cover_image'] ?? null;
        if ($file === null || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        $allowed = ['jpeg', 'jpg', 'png', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            return null;
        }
        $dir = public_path('storage/books/covers');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = uniqid('cover_', true) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
            return null;
        }
        return 'books/covers/' . $filename;
    }
}
