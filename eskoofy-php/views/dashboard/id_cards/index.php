<?php $pageTitle = 'ID Cards'; ?>
<?php ob_start(); ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Student ID Cards</h1>
</div>

<div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Generate ID Cards</h2>
        <form action="/dashboard/id-cards/generate" method="POST" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Class *</label>
                <select name="class_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="">Select Class</option>
                    <?php if (!empty($classes)): ?>
                        <?php foreach ($classes as $class): ?>
                        <option value="<?= e($class['id']) ?>"><?= e($class['name']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Section</label>
                <select name="section_id" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="">All Sections</option>
                </select>
            </div>
            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="all_students" value="1" class="rounded border-gray-300 text-blue-600 mr-2">
                    <span class="text-sm text-gray-700">Generate for all active students</span>
                </label>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Generate</button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Preview</h2>
        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
            <div class="w-48 mx-auto bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="bg-blue-600 text-white p-3 text-center">
                    <p class="font-bold text-sm"><?= e(config('school.name', 'School')) ?></p>
                </div>
                <div class="p-4 text-center">
                    <div class="w-16 h-16 bg-gray-200 rounded-full mx-auto mb-2"></div>
                    <p class="font-bold text-sm">Student Name</p>
                    <p class="text-xs text-gray-500">Class: X | Section: A</p>
                    <p class="text-xs text-gray-500">Roll: 001</p>
                    <p class="text-xs text-gray-500">ID: STU-001</p>
                </div>
                <div class="bg-gray-50 p-2 text-center text-xs text-gray-400">
                    <?= e(config('school.phone', '')) ?>
                </div>
            </div>
            <p class="text-gray-500 text-sm mt-4">Select a class to generate ID cards</p>
        </div>
    </div>
</div>

<?php if (!empty($generatedCards)): ?>
<div class="bg-white rounded-xl shadow-sm p-6 mt-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-bold">Generated ID Cards (<?= count($generatedCards) ?>)</h2>
        <button onclick="window.print()" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">🖨️ Print All</button>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <?php foreach ($generatedCards as $card): ?>
        <div class="text-center text-sm">
            <div class="border rounded-lg p-3 mb-1">
                <p class="font-bold"><?= e($card['student']['name'] ?? '') ?></p>
                <p class="text-xs text-gray-500"><?= e($card['student']['student_id'] ?? '') ?></p>
            </div>
            <a href="/dashboard/id-cards/<?= e($card['id']) ?>/print" target="_blank" class="text-blue-600 hover:underline text-xs">Print</a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>