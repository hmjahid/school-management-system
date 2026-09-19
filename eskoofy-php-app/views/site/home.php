<?php $pageTitle = 'Home'; ?>
<?php ob_start(); ?>

<?php
$hc = is_array($homeContent->content ?? null) ? $homeContent->content : [];
$hero = $hc['hero'] ?? [];
$highlights = $hc['highlights'] ?? [];
$principal = $hc['principal'] ?? [];
$testimonials = $hc['testimonials'] ?? [];
$features = $hc['features'] ?? [];
$heroImg = $hero['background_image'] ?? null;
$headline = $hero['headline'] ?? $hero['title'] ?? config('school.motto', 'Empowering Minds, Shaping Futures');
$sub = $hero['motto'] ?? $hero['subtitle'] ?? config('school.description', '');
$principalMessage = $principal['message'] ?? '';
$featuresH = $hc['features_heading'] ?? [];
$statsL = $hc['stats'] ?? [];
$teachersH = $hc['teachers'] ?? [];
$testimonialsH = $hc['testimonials_heading'] ?? [];
$remarkableH = $hc['remarkable_students'] ?? [];
$committeeH = $hc['committee_members'] ?? [];
$eventsH = $hc['events'] ?? [];
$newsH = $hc['news'] ?? [];
$highlightsH = $hc['highlights_heading'] ?? [];
$partnersH = $hc['partners_heading'] ?? [];
$partners = $hc['partners'] ?? [];

$testimonialsFallback = !empty($testimonials) ? $testimonials : [
    ['quote' => 'Excellent education and caring teachers.', 'name' => 'Parent', 'role' => 'Guardian'],
    ['quote' => 'My children love going to school every day.', 'name' => 'Alumni Parent', 'role' => 'Community'],
];
$highlightsFallback = !empty($highlights) ? $highlights : [
    'Experienced & qualified teachers',
    'Modern science & computer labs',
    'Safe campus with CCTV monitoring',
    'Regular parent-teacher meetings',
    'Transport facility available',
    'Co-curricular activities',
];
?>

<!-- Hero Section -->
<section class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-6"><?= e($headline) ?></h1>
        <p class="text-xl text-blue-100 mb-8 max-w-3xl mx-auto"><?= e($sub) ?></p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="/admission" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-blue-50 transition">Apply for Admission</a>
            <a href="/about" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition">Learn More</a>
        </div>
    </div>
</section>

<!-- Features Section -->
<?php if (!empty($features)): ?>
<section class="bg-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-12 text-center">
            <h2 class="mb-4 text-3xl font-bold text-gray-900"><?= e($featuresH['title'] ?? 'Why Choose Us') ?></h2>
            <div class="mx-auto h-1 w-20 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full"></div>
            <?php if (!empty($featuresH['intro'])): ?>
                <p class="mx-auto mt-4 max-w-3xl text-lg text-gray-600"><?= e($featuresH['intro']) ?></p>
            <?php endif; ?>
        </div>
        <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($features as $index => $feature): ?>
            <div class="rounded-2xl bg-white p-8 shadow-md ring-1 ring-gray-100 transition-all hover:shadow-xl">
                <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-50 text-2xl text-blue-600 font-bold"><?= $index + 1 ?></div>
                <h3 class="mb-3 text-center text-xl font-semibold text-gray-900"><?= e($feature['title'] ?? '') ?></h3>
                <p class="text-center text-gray-600 leading-relaxed"><?= e($feature['description'] ?? '') ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Stats Section -->
<section class="bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-900 py-16 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 gap-8 md:grid-cols-4 text-center">
            <div class="p-6 rounded-xl bg-white/5 border border-white/10">
                <div class="text-4xl font-bold"><?= e($stats['students'] ?? 0) ?>+</div>
                <div class="mt-2 text-sm text-blue-200 uppercase tracking-wider"><?= e($statsL['students'] ?? 'Students') ?></div>
            </div>
            <div class="p-6 rounded-xl bg-white/5 border border-white/10">
                <div class="text-4xl font-bold"><?= e($stats['teachers'] ?? 0) ?>+</div>
                <div class="mt-2 text-sm text-blue-200 uppercase tracking-wider"><?= e($statsL['faculty'] ?? 'Teachers') ?></div>
            </div>
            <div class="p-6 rounded-xl bg-white/5 border border-white/10">
                <div class="text-4xl font-bold"><?= e($stats['years'] ?? 0) ?>+</div>
                <div class="mt-2 text-sm text-blue-200 uppercase tracking-wider"><?= e($statsL['years'] ?? 'Years of Excellence') ?></div>
            </div>
            <div class="p-6 rounded-xl bg-white/5 border border-white/10">
                <div class="text-4xl font-bold"><?= e($stats['awards'] ?? 0) ?>+</div>
                <div class="mt-2 text-sm text-blue-200 uppercase tracking-wider"><?= e($statsL['awards'] ?? 'Awards') ?></div>
            </div>
        </div>
    </div>
</section>

<!-- Principal's Message -->
<?php if (!empty($principalMessage)): ?>
<section class="bg-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-10 text-center">
            <h2 class="text-3xl font-bold text-gray-900"><?= e($principal['section_title'] ?? "Principal's Message") ?></h2>
            <div class="mx-auto mt-3 h-1 w-20 rounded-full bg-gradient-to-r from-orange-400 to-orange-600"></div>
        </div>
        <div class="grid items-center gap-12 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <div class="relative mx-auto w-full max-w-xs">
                    <?php if (!empty($principal['photo'])): ?>
                        <img src="<?= e($principal['photo']) ?>" alt="<?= e($principal['name'] ?? 'Principal') ?>" class="aspect-[4/3] w-full rounded-2xl object-cover shadow-xl">
                    <?php else: ?>
                        <div class="flex aspect-[4/3] w-full items-center justify-center rounded-2xl bg-gradient-to-br from-blue-100 to-indigo-100">
                            <svg class="h-20 w-20 text-blue-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($principal['name'])): ?>
                        <div class="absolute -bottom-5 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-orange-500 to-orange-600 px-5 py-2 text-sm font-semibold text-white shadow-lg">
                            <?= e($principal['name']) ?>
                            <?php if (!empty($principal['designation'])): ?>
                                <span class="font-normal text-orange-100">&middot; <?= e($principal['designation']) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="lg:col-span-3">
                <div class="relative rounded-2xl border border-orange-100 bg-orange-50/50 p-8">
                    <svg class="absolute -top-5 -left-4 h-12 w-12 text-orange-300" fill="currentColor" viewBox="0 0 24 24"><path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10H14.017zM0 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151C7.544 6.068 5.982 8.79 5.982 11H10v10H0z"/></svg>
                    <blockquote class="mt-2 text-lg leading-relaxed text-gray-700"><?= e($principalMessage) ?></blockquote>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Our Teachers -->
<?php if ($teachers->isNotEmpty()): ?>
<section class="bg-slate-50 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-12 text-center">
            <h2 class="mb-4 text-3xl font-bold text-gray-900"><?= e($teachersH['title'] ?? 'Our Teachers') ?></h2>
            <div class="mx-auto h-1 w-20 bg-gradient-to-r from-orange-400 to-orange-600 rounded-full"></div>
            <?php if (!empty($teachersH['intro'])): ?>
                <p class="mx-auto mt-4 max-w-3xl text-lg text-gray-600"><?= e($teachersH['intro']) ?></p>
            <?php endif; ?>
        </div>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($teachers as $teacher): ?>
            <?php
                $name = $teacher->user?->name ?? $teacher->name ?? 'Teacher';
                $initials = implode('', array_map(fn($w) => strtoupper(substr($w, 0, 1)), explode(' ', $name)));
            ?>
            <div class="rounded-2xl bg-white p-6 shadow-md ring-1 ring-gray-100 text-center transition-all hover:shadow-xl">
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 text-2xl font-bold text-blue-600 ring-4 ring-white shadow-lg">
                    <?= e($initials) ?>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-gray-900"><?= e($name) ?></h3>
                <?php if ($teacher->qualification): ?>
                    <p class="mt-1 text-sm text-gray-500"><?= e($teacher->qualification) ?></p>
                <?php endif; ?>
                <?php if ($teacher->subjects): ?>
                    <p class="mt-2 text-xs text-gray-400"><?= e(is_array($teacher->subjects) ? implode(', ', $teacher->subjects) : $teacher->subjects) ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-8 text-center">
            <a href="/faculty" class="inline-flex items-center gap-1.5 font-medium text-blue-600 hover:text-blue-800">
                <?= e($teachersH['view_all'] ?? 'View All Faculty') ?> &rarr;
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Managing Committee -->
<?php if ($committeeMembers->isNotEmpty()): ?>
<section class="bg-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-12 text-center">
            <h2 class="mb-4 text-3xl font-bold text-gray-900"><?= e($committeeH['title'] ?? 'Managing Committee') ?></h2>
            <div class="mx-auto h-1 w-20 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full"></div>
            <?php if (!empty($committeeH['intro'])): ?>
                <p class="mx-auto mt-4 max-w-3xl text-lg text-gray-600"><?= e($committeeH['intro']) ?></p>
            <?php endif; ?>
        </div>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($committeeMembers as $member): ?>
            <?php
                $name = $member->localizedName();
                $initials = implode('', array_map(fn($w) => strtoupper(substr($w, 0, 1)), explode(' ', $name)));
                $photo = $member->photo ?? null;
            ?>
            <div class="rounded-2xl bg-white p-6 shadow-md ring-1 ring-gray-100 text-center transition-all hover:shadow-xl">
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 text-2xl font-bold text-blue-600 ring-4 ring-white shadow-lg overflow-hidden">
                    <?php if ($photo): ?>
                        <img src="<?= e($photo) ?>" alt="<?= e($name) ?>" class="h-full w-full object-cover">
                    <?php else: ?>
                        <?= e($initials) ?>
                    <?php endif; ?>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-gray-900"><?= e($name) ?></h3>
                <p class="mt-1 text-sm text-blue-600 font-medium"><?= e($member->localizedDesignation()) ?></p>
                <?php if ($member->phone): ?>
                    <p class="mt-2 text-xs text-gray-400"><?= e($member->phone) ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-8 text-center">
            <a href="/committee" class="inline-flex items-center gap-1.5 font-medium text-blue-600 hover:text-blue-800">
                <?= e($committeeH['view_all'] ?? 'View All Members') ?> &rarr;
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Testimonials -->
<?php if (!empty($testimonialsFallback)): ?>
<section class="bg-slate-50 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-12 text-center">
            <h2 class="mb-4 text-3xl font-bold text-gray-900"><?= e($testimonialsH['title'] ?? 'What People Say') ?></h2>
            <div class="mx-auto h-1 w-20 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full"></div>
        </div>
        <div class="mx-auto grid max-w-5xl gap-8 md:grid-cols-2">
            <?php foreach ($testimonialsFallback as $t): ?>
            <div class="relative rounded-2xl bg-white p-8 shadow-md ring-1 ring-gray-100">
                <svg class="absolute top-6 left-6 h-10 w-10 text-orange-200" fill="currentColor" viewBox="0 0 24 24"><path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10H14.017zM0 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151C7.544 6.068 5.982 8.79 5.982 11H10v10H0z"/></svg>
                <p class="relative z-10 mb-6 mt-4 text-lg text-gray-700 leading-relaxed"><?= e($t['quote'] ?? '') ?></p>
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 text-lg font-bold text-blue-600">
                        <?= e(strtoupper(substr($t['name'] ?? 'A', 0, 1))) ?>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900"><?= e($t['name'] ?? '') ?></h4>
                        <p class="text-sm text-gray-500"><?= e($t['role'] ?? '') ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Remarkable Students -->
<?php if ($remarkableStudents->isNotEmpty()): ?>
<section class="bg-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-12 text-center">
            <h2 class="mb-4 text-3xl font-bold text-gray-900"><?= e($remarkableH['title'] ?? 'Bright Students') ?></h2>
            <div class="mx-auto h-1 w-20 bg-gradient-to-r from-orange-400 to-orange-600 rounded-full"></div>
            <?php if (!empty($remarkableH['intro'])): ?>
                <p class="mx-auto mt-4 max-w-3xl text-lg text-gray-600"><?= e($remarkableH['intro']) ?></p>
            <?php endif; ?>
        </div>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($remarkableStudents as $student): ?>
            <?php
                $name = $student->user?->name ?? $student->name ?? 'Student';
                $initials = implode('', array_map(fn($w) => strtoupper(substr($w, 0, 1)), explode(' ', $name)));
            ?>
            <div class="rounded-2xl bg-white p-6 shadow-md ring-1 ring-gray-100 text-center transition-all hover:shadow-xl">
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-amber-100 to-orange-100 text-2xl font-bold text-orange-600 ring-4 ring-white shadow-lg">
                    <?= e($initials) ?>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-gray-900"><?= e($name) ?></h3>
                <?php if ($student->class): ?>
                    <p class="mt-1 text-sm text-gray-500"><?= e($student->class->name) ?></p>
                <?php endif; ?>
                <?php if ($student->achievement): ?>
                    <p class="mt-2 text-xs text-orange-600 font-medium leading-relaxed"><?= e($student->achievement) ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Photo Slider / Gallery -->
<?php if (!empty($sliderSlides) || $sliderFallback->isNotEmpty()): ?>
<section class="bg-slate-100 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-10 text-center">
            <h2 class="text-3xl font-bold text-gray-900">Gallery</h2>
            <div class="mt-2 h-1 w-20 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full"></div>
        </div>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            <?php
            $slides = !empty($sliderSlides) ? $sliderSlides : $sliderFallback->all();
            foreach ($slides as $slide):
                $img = $slide['image'] ?? ($slide['image_path'] ?? ($slide['image_url'] ?? null));
                $slideTitle = $slide['title'] ?? '';
                $caption = $slide['caption'] ?? '';
                if (empty($img)) continue;
            ?>
            <div class="group relative overflow-hidden rounded-2xl bg-white shadow-md ring-1 ring-gray-100">
                <div class="h-56 overflow-hidden bg-slate-200">
                    <img src="<?= e($img) ?>" alt="<?= e($slideTitle) ?>" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent"></div>
                <div class="absolute bottom-0 w-full p-5">
                    <?php if ($slideTitle): ?>
                        <h3 class="text-lg font-bold text-white"><?= e($slideTitle) ?></h3>
                    <?php endif; ?>
                    <?php if ($caption): ?>
                        <p class="mt-1 text-sm text-white/80"><?= e($caption) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-8 text-center">
            <a href="/gallery" class="inline-flex items-center gap-1.5 font-medium text-blue-600 hover:text-blue-800">View Full Gallery &rarr;</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Upcoming Events -->
<?php if ($upcomingEvents->isNotEmpty()): ?>
<section class="bg-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-10 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-3xl font-bold text-gray-900"><?= e($eventsH['title'] ?? 'Upcoming Events') ?></h2>
                <div class="mt-2 h-1 w-20 bg-gradient-to-r from-orange-400 to-orange-600 rounded-full"></div>
            </div>
            <a href="/events" class="inline-flex items-center gap-1 font-medium text-blue-600 hover:text-blue-800"><?= e($eventsH['view_all'] ?? 'View All') ?> &rarr;</a>
        </div>
        <div class="grid gap-6 md:grid-cols-3">
            <?php foreach ($upcomingEvents->take(6) as $ev): ?>
            <div class="rounded-2xl bg-white p-6 shadow-md ring-1 ring-gray-100 transition-all hover:shadow-xl">
                <div class="inline-flex items-center gap-2 rounded-lg bg-orange-50 px-3 py-1.5 text-sm font-semibold text-orange-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <?= e(date('M j, Y', strtotime($ev->start_date ?? 'now'))) ?>
                </div>
                <h3 class="mt-4 text-lg font-semibold text-gray-900"><?= e($ev->title) ?></h3>
                <?php if ($ev->location): ?>
                    <p class="mt-2 text-sm text-gray-500"><?= e($ev->location) ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Latest News -->
<?php if ($latestNews->isNotEmpty()): ?>
<section class="bg-slate-50 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-10 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-3xl font-bold text-gray-900"><?= e($newsH['title'] ?? 'Latest News') ?></h2>
                <div class="mt-2 h-1 w-20 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full"></div>
            </div>
            <a href="/news" class="inline-flex items-center gap-1 font-medium text-blue-600 hover:text-blue-800"><?= e($newsH['view_all'] ?? 'View All') ?> &rarr;</a>
        </div>
        <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($latestNews->take(6) as $item): ?>
            <div class="group overflow-hidden rounded-2xl bg-white shadow-md ring-1 ring-gray-100 transition-all hover:shadow-xl">
                <div class="h-48 overflow-hidden bg-slate-200">
                    <?php if (!empty($item->image_url)): ?>
                        <img src="<?= e($item->image_url) ?>" alt="" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
                    <?php else: ?>
                        <div class="h-full w-full bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center">
                            <svg class="h-12 w-12 text-blue-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="p-6">
                    <?php if (!empty($item->published_at)): ?>
                        <div class="mb-2 text-sm text-slate-500"><?= e(date('M j, Y', strtotime($item->published_at))) ?></div>
                    <?php endif; ?>
                    <h3 class="mb-3 text-xl font-semibold text-gray-900"><?= e($item->title) ?></h3>
                    <p class="mb-4 text-slate-600 leading-relaxed"><?= e(truncate(strip_tags($item->content ?? ''), 140)) ?></p>
                    <a href="/news/<?= e($item->slug ?? '') ?>" class="inline-flex items-center gap-1 font-medium text-blue-600 hover:text-blue-800">Read More &rarr;</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Highlights -->
<?php if (!empty($highlightsFallback)): ?>
<section class="bg-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="mb-4 text-center text-2xl font-bold text-gray-900"><?= e($highlightsH['title'] ?? 'Highlights') ?></h2>
        <div class="mx-auto mb-8 h-1 w-20 bg-gradient-to-r from-orange-400 to-orange-600 rounded-full"></div>
        <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($highlightsFallback as $h): ?>
            <li class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-5 py-4 text-gray-700">
                <svg class="h-5 w-5 shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                <?= e($h) ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<?php endif; ?>

<!-- CTA Banner -->
<section class="relative overflow-hidden bg-gradient-to-r from-blue-700 via-blue-800 to-indigo-900 py-20 text-white">
    <div class="relative z-10 mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
        <h2 class="mb-6 text-4xl font-bold">Ready to Join Us?</h2>
        <p class="mx-auto mb-10 max-w-3xl text-xl text-blue-100">Start your educational journey with us. Our admission process is simple and straightforward.</p>
        <div class="flex flex-col justify-center gap-4 sm:flex-row">
            <a href="/admission" class="inline-flex items-center gap-2 rounded-xl bg-white px-10 py-4 text-lg font-semibold text-blue-800 shadow-lg transition-all hover:bg-gray-100">Start Admission Process &rarr;</a>
            <a href="/contact" class="inline-flex items-center gap-2 rounded-xl border-2 border-white/30 bg-white/10 px-10 py-4 text-lg font-semibold text-white backdrop-blur-sm transition-all hover:bg-white/20">Contact Us</a>
        </div>
    </div>
</section>

<!-- Partners -->
<?php if (!empty($partners)): ?>
<section class="bg-white py-12 border-t border-slate-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <p class="mb-8 text-center text-sm font-semibold uppercase tracking-wider text-slate-400"><?= e($partnersH['title'] ?? 'Our Partners') ?></p>
        <div class="flex flex-wrap items-center justify-center gap-8 md:gap-14">
            <?php foreach ($partners as $partner): ?>
            <a href="<?= e($partner['url'] ?? '#') ?>" target="_blank" rel="noopener noreferrer" class="flex flex-col items-center gap-2 opacity-60 transition hover:opacity-100" title="<?= e($partner['name'] ?? '') ?>">
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-50">
                    <svg class="h-7 w-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </div>
                <span class="text-xs font-medium text-slate-500"><?= e($partner['name'] ?? '') ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>
