<?php $pageTitle = 'Class Routine'; ?>
<?php ob_start(); ?>

<section class="bg-blue-600 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">Class Routine</h1>
        <p class="text-blue-100 text-lg">View your weekly class schedule</p>
    </div>
</section>

<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
            <form action="/routine" method="GET" class="flex flex-col sm:flex-row gap-4">
                <select name="class_id" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">Select Class</option>
                    <?php if (!empty($classes)): ?>
                        <?php foreach ($classes as $class): ?>
                        <option value="<?= e($class->id) ?>" <?= ($class_id ?? '') == $class->id ? 'selected' : '' ?>><?= e($class->name) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <select name="section_id" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">Select Section</option>
                    <?php if (!empty($sections)): ?>
                        <?php foreach ($sections as $section): ?>
                        <option value="<?= e($section->id) ?>" <?= ($section_id ?? '') == $section->id ? 'selected' : '' ?>><?= e($section->name) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">View Routine</button>
            </form>
        </div>

        <?php if (!empty($routine)): ?>
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="py-3 px-4 font-semibold border-b">Time</th>
                            <?php foreach ($days as $day): ?>
                            <th class="py-3 px-4 font-semibold border-b text-center"><?= e($day) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($periods as $period): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-3 px-4 font-medium whitespace-nowrap">
                                <?= e($period->start_time) ?> - <?= e($period->end_time) ?>
                            </td>
                            <?php foreach ($days as $day): ?>
                            <td class="py-3 px-4 text-center text-sm">
                                <?php if (isset($routine[$day][$period->id])): ?>
                                    <?php $slot = $routine[$day][$period->id]; ?>
                                    <div class="font-medium"><?= e($slot->subject->name ?? '') ?></div>
                                    <div class="text-gray-500 text-xs"><?= e($slot->teacher->name ?? '') ?></div>
                                <?php else: ?>
                                    <span class="text-gray-300">-</span>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php elseif (isset($class_id)): ?>
        <div class="text-center py-12">
            <p class="text-gray-500 text-lg">No routine found for the selected class/section.</p>
        </div>
        <?php else: ?>
        <div class="text-center py-12">
            <p class="text-gray-500 text-lg">Please select a class to view the routine.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>