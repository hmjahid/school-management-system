<?php $pageTitle = 'Book Issues'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Book Issues & Returns</h1>
    <a href="/dashboard/library" class="text-gray-600 hover:text-gray-800">← Back to Library</a>
    <button onclick="document.getElementById('issue-modal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">+ Issue Book</button>
</div>

<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <form action="/dashboard/library/issues" method="GET" class="flex flex-col sm:flex-row gap-4">
        <input type="text" name="search" value="<?= e($search ?? '') ?>" placeholder="Search..." class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
        <select name="status" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            <option value="">All Status</option>
            <option value="issued" <?= ($status ?? '') == 'issued' ? 'selected' : '' ?>>Issued</option>
            <option value="returned" <?= ($status ?? '') == 'returned' ? 'selected' : '' ?>>Returned</option>
            <option value="overdue" <?= ($status ?? '') == 'overdue' ? 'selected' : '' ?>>Overdue</option>
        </select>
        <button type="submit" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50">
                    <th class="py-3 px-4 font-semibold border-b">Book</th>
                    <th class="py-3 px-4 font-semibold border-b">Student</th>
                    <th class="py-3 px-4 font-semibold border-b">Issue Date</th>
                    <th class="py-3 px-4 font-semibold border-b">Due Date</th>
                    <th class="py-3 px-4 font-semibold border-b">Return Date</th>
                    <th class="py-3 px-4 font-semibold border-b">Status</th>
                    <th class="py-3 px-4 font-semibold border-b">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($issues)): ?>
                    <?php foreach ($issues as $issue): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium"><?= e($issue['book_title'] ?? '') ?></td>
                        <td class="py-3 px-4"><?= e($issue['member_name'] ?? '') ?></td>
                        <td class="py-3 px-4 text-sm"><?= e(date('M d, Y', strtotime($issue['issued_at'] ?? 'now'))) ?></td>
                        <td class="py-3 px-4 text-sm"><?= e(date('M d, Y', strtotime($issue['due_date'] ?? 'now'))) ?></td>
                        <td class="py-3 px-4 text-sm"><?= e(!empty($issue['returned_at']) ? date('M d, Y', strtotime($issue['returned_at'])) : '-') ?></td>
                        <td class="py-3 px-4">
                            <?php
                            $issueStatus = $issue['status'] ?? 'issued';
                            $colors = ['issued' => 'bg-blue-100 text-blue-700', 'returned' => 'bg-green-100 text-green-700', 'overdue' => 'bg-red-100 text-red-700'];
                            ?>
                            <span class="px-2 py-1 text-xs rounded-full <?= $colors[$issueStatus] ?? 'bg-gray-100 text-gray-700' ?>"><?= e(ucfirst($issueStatus)) ?></span>
                        </td>
                        <td class="py-3 px-4">
                            <?php if ($issueStatus == 'issued' || $issueStatus == 'overdue'): ?>
                            <form action="/dashboard/library/issues/<?= e($issue['id']) ?>/return" method="POST" class="inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="text-green-600 hover:underline text-sm">Return</button>
                            </form>
                            <?php else: ?>
                            <span class="text-gray-400 text-sm">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="py-8 text-center text-gray-500">No issues found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="issue-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">Issue Book</h2>
        <form action="/dashboard/library/issues" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Book *</label>
                <select name="book_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="">Select Book</option>
                    <?php if (!empty($books)): ?>
                        <?php foreach ($books as $book): ?>
                        <option value="<?= e($book['id']) ?>"><?= e($book['title'] ?? '') ?> (<?= e($book['available'] ?? 0) ?> available)</option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Student *</label>
                <input type="text" name="student_id" placeholder="Student ID" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Due Date *</label>
                <input type="date" name="due_date" value="<?= date('Y-m-d', strtotime('+14 days')) ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('issue-modal').classList.add('hidden')" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Issue</button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>