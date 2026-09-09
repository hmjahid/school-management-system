<?php $pageTitle = 'Transport'; ?>
<?php ob_start(); ?>

<section class="bg-blue-600 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">Transport</h1>
        <p class="text-blue-100 text-lg">School bus routes, vehicles and service areas</p>
    </div>
</section>

<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold mb-6">Our Vehicles</h2>
        <div class="grid md:grid-cols-3 gap-6 mb-12">
            <?php if (!empty($vehicles)): ?>
                <?php foreach ($vehicles as $vehicle): ?>
                <div class="bg-white p-6 rounded-xl shadow-sm">
                    <h3 class="text-lg font-bold mb-2">🚌 <?= e($vehicle['number']) ?></h3>
                    <p class="text-sm text-gray-600">Type: <?= e($vehicle['type'] ?? 'N/A') ?></p>
                    <p class="text-sm text-gray-600">Capacity: <?= e($vehicle['capacity'] ?? 0) ?></p>
                    <?php if (!empty($vehicle['driver_name'])): ?>
                    <p class="text-sm text-gray-600">Driver: <?= e($vehicle['driver_name']) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="col-span-3 text-center text-gray-500">No vehicles listed.</p>
            <?php endif; ?>
        </div>

        <h2 class="text-2xl font-bold mb-6">Routes &amp; Areas Covered</h2>
        <div class="grid md:grid-cols-2 gap-6">
            <?php if (!empty($routes)): ?>
                <?php foreach ($routes as $route): ?>
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h3 class="text-lg font-bold"><?= e($route['name']) ?></h3>
                            <p class="text-xs text-gray-500">Code: <?= e($route['code']) ?></p>
                        </div>
                        <span class="bg-blue-100 text-blue-700 text-xs px-2 py-1 rounded-full"><?= e(format_currency((float)$route['fare'])) ?></span>
                    </div>
                    <?php if (!empty($route['vehicle_number'])): ?>
                    <p class="text-sm text-gray-600 mb-1">Vehicle: <?= e($route['vehicle_number']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($route['driver_name'])): ?>
                    <p class="text-sm text-gray-600 mb-1">Driver: <?= e($route['driver_name']) ?> (<?= e($route['driver_phone'] ?? '') ?>)</p>
                    <?php endif; ?>
                    <?php if (!empty($stops[$route['id']])): ?>
                    <p class="text-xs font-semibold text-gray-700 mt-3 mb-1">Stops:</p>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <?php foreach ($stops[$route['id']] as $stop): ?>
                        <li>📍 <?= e($stop['name']) ?> <span class="text-gray-400 text-xs">(<?= e($stop['pickup_time'] ?? '') ?> – <?= e($stop['drop_time'] ?? '') ?>)</span></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="col-span-2 text-center text-gray-500">No routes available yet.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>
