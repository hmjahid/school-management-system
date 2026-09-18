<?php $title = __('features.title'); $siteTitle = $title; ?>

<section class="esk-hero text-white">
    <div class="relative z-10 max-w-7xl mx-auto px-4 py-16 text-center">
        <span class="inline-block text-xs uppercase tracking-widest bg-blue-500/20 text-blue-200 px-3 py-1 rounded-full border border-blue-400/30 mb-6"><?= __('features.badge') ?></span>
        <h1 class="text-4xl md:text-5xl font-extrabold"><?= __('features.title') ?></h1>
        <p class="mt-4 text-slate-300 text-lg max-w-3xl mx-auto"><?= __('features.sub') ?></p>
    </div>
</section>

<div class="max-w-7xl mx-auto px-4 py-16">

    <!-- Role tabs -->
    <div class="flex flex-wrap justify-center gap-2 mb-12" data-role-tabs>
        <button type="button" data-role-tab="admin" class="border-b-2 border-blue-600 text-blue-600 font-semibold px-5 py-2 text-sm"><?= __('features.role_admin') ?></button>
        <button type="button" data-role-tab="teacher" class="border-b-2 border-transparent text-slate-500 hover:text-slate-700 font-semibold px-5 py-2 text-sm"><?= __('features.role_teacher') ?></button>
        <button type="button" data-role-tab="parent" class="border-b-2 border-transparent text-slate-500 hover:text-slate-700 font-semibold px-5 py-2 text-sm"><?= __('features.role_parent') ?></button>
    </div>

    <!-- Admin panel -->
    <div data-role-panel="admin">
        <div class="grid md:grid-cols-3 gap-8">
            <div class="bg-white rounded-3xl p-8 border border-slate-200 esk-card-hover">
                <div class="text-2xl mb-3">🎓</div>
                <div class="text-lg text-blue-600 font-bold mb-2"><?= __('features.academic') ?></div>
                <p class="text-sm text-slate-600 leading-relaxed"><?= __('features.academic_desc') ?></p>
            </div>
            <div class="bg-white rounded-3xl p-8 border border-slate-200 esk-card-hover">
                <div class="text-2xl mb-3">👥</div>
                <div class="text-lg text-blue-600 font-bold mb-2"><?= __('features.students') ?></div>
                <p class="text-sm text-slate-600 leading-relaxed"><?= __('features.students_desc') ?></p>
            </div>
            <div class="bg-white rounded-3xl p-8 border border-slate-200 esk-card-hover">
                <div class="text-2xl mb-3">📅</div>
                <div class="text-lg text-blue-600 font-bold mb-2"><?= __('features.attendance') ?></div>
                <p class="text-sm text-slate-600 leading-relaxed"><?= __('features.attendance_desc') ?></p>
            </div>
            <div class="bg-white rounded-3xl p-8 border border-slate-200 esk-card-hover">
                <div class="text-2xl mb-3">💰</div>
                <div class="text-lg text-blue-600 font-bold mb-2"><?= __('features.finance') ?></div>
                <p class="text-sm text-slate-600 leading-relaxed"><?= __('features.finance_desc') ?></p>
            </div>
            <div class="bg-white rounded-3xl p-8 border border-slate-200 esk-card-hover">
                <div class="text-2xl mb-3">📨</div>
                <div class="text-lg text-blue-600 font-bold mb-2"><?= __('features.comms') ?></div>
                <p class="text-sm text-slate-600 leading-relaxed"><?= __('features.comms_desc') ?></p>
            </div>
            <div class="bg-white rounded-3xl p-8 border border-slate-200 esk-card-hover">
                <div class="text-2xl mb-3">🔑</div>
                <div class="text-lg text-blue-600 font-bold mb-2"><?= __('features.api') ?></div>
                <p class="text-sm text-slate-600 leading-relaxed"><?= __('features.api_desc') ?></p>
            </div>
        </div>

        <!-- Module groups -->
        <div class="mt-16">
            <h2 class="text-2xl font-extrabold text-center mb-2"><?= __('features.modules_heading') ?></h2>
            <p class="text-slate-500 text-center mb-10"><?= __('features.modules_sub') ?></p>
            <div class="grid md:grid-cols-4 gap-6">
                <?php foreach (['main', 'academic', 'daily', 'finance', 'hr', 'documents', 'library', 'system'] as $g): ?>
                    <div class="bg-white rounded-2xl border border-slate-200 p-6">
                        <div class="font-bold text-slate-900 mb-3"><?= __('features.group.' . $g) ?></div>
                        <ul class="space-y-2 text-sm text-slate-600">
                            <?php foreach (['a', 'b', 'c', 'd'] as $i): ?>
                                <li class="esk-check"><?= __('features.group.' . $g . '.' . $i) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Teacher panel -->
    <div data-role-panel="teacher" class="hidden">
        <div class="grid md:grid-cols-3 gap-8">
            <div class="bg-white rounded-3xl p-8 border border-slate-200 esk-card-hover">
                <div class="text-2xl mb-3">📝</div>
                <div class="text-lg text-blue-600 font-bold mb-2"><?= __('features.teacher.classes') ?></div>
                <p class="text-sm text-slate-600 leading-relaxed"><?= __('features.teacher.classes_desc') ?></p>
            </div>
            <div class="bg-white rounded-3xl p-8 border border-slate-200 esk-card-hover">
                <div class="text-2xl mb-3">📊</div>
                <div class="text-lg text-blue-600 font-bold mb-2"><?= __('features.teacher.grades') ?></div>
                <p class="text-sm text-slate-600 leading-relaxed"><?= __('features.teacher.grades_desc') ?></p>
            </div>
            <div class="bg-white rounded-3xl p-8 border border-slate-200 esk-card-hover">
                <div class="text-2xl mb-3">🧾</div>
                <div class="text-lg text-blue-600 font-bold mb-2"><?= __('features.teacher.assignments') ?></div>
                <p class="text-sm text-slate-600 leading-relaxed"><?= __('features.teacher.assignments_desc') ?></p>
            </div>
            <div class="bg-white rounded-3xl p-8 border border-slate-200 esk-card-hover">
                <div class="text-2xl mb-3">🏷️</div>
                <div class="text-lg text-blue-600 font-bold mb-2"><?= __('features.teacher.attendance') ?></div>
                <p class="text-sm text-slate-600 leading-relaxed"><?= __('features.teacher.attendance_desc') ?></p>
            </div>
            <div class="bg-white rounded-3xl p-8 border border-slate-200 esk-card-hover">
                <div class="text-2xl mb-3">💬</div>
                <div class="text-lg text-blue-600 font-bold mb-2"><?= __('features.teacher.comm') ?></div>
                <p class="text-sm text-slate-600 leading-relaxed"><?= __('features.teacher.comm_desc') ?></p>
            </div>
            <div class="bg-white rounded-3xl p-8 border border-slate-200 esk-card-hover">
                <div class="text-2xl mb-3">🗓️</div>
                <div class="text-lg text-blue-600 font-bold mb-2"><?= __('features.teacher.routine') ?></div>
                <p class="text-sm text-slate-600 leading-relaxed"><?= __('features.teacher.routine_desc') ?></p>
            </div>
        </div>
    </div>

    <!-- Parent panel -->
    <div data-role-panel="parent" class="hidden">
        <div class="grid md:grid-cols-3 gap-8">
            <div class="bg-white rounded-3xl p-8 border border-slate-200 esk-card-hover">
                <div class="text-2xl mb-3">📱</div>
                <div class="text-lg text-blue-600 font-bold mb-2"><?= __('features.parent.portal') ?></div>
                <p class="text-sm text-slate-600 leading-relaxed"><?= __('features.parent.portal_desc') ?></p>
            </div>
            <div class="bg-white rounded-3xl p-8 border border-slate-200 esk-card-hover">
                <div class="text-2xl mb-3">💰</div>
                <div class="text-lg text-blue-600 font-bold mb-2"><?= __('features.parent.fees') ?></div>
                <p class="text-sm text-slate-600 leading-relaxed"><?= __('features.parent.fees_desc') ?></p>
            </div>
            <div class="bg-white rounded-3xl p-8 border border-slate-200 esk-card-hover">
                <div class="text-2xl mb-3">📊</div>
                <div class="text-lg text-blue-600 font-bold mb-2"><?= __('features.parent.results') ?></div>
                <p class="text-sm text-slate-600 leading-relaxed"><?= __('features.parent.results_desc') ?></p>
            </div>
            <div class="bg-white rounded-3xl p-8 border border-slate-200 esk-card-hover">
                <div class="text-2xl mb-3">🏫</div>
                <div class="text-lg text-blue-600 font-bold mb-2"><?= __('features.parent.attendance') ?></div>
                <p class="text-sm text-slate-600 leading-relaxed"><?= __('features.parent.attendance_desc') ?></p>
            </div>
            <div class="bg-white rounded-3xl p-8 border border-slate-200 esk-card-hover">
                <div class="text-2xl mb-3">💬</div>
                <div class="text-lg text-blue-600 font-bold mb-2"><?= __('features.parent.messages') ?></div>
                <p class="text-sm text-slate-600 leading-relaxed"><?= __('features.parent.messages_desc') ?></p>
            </div>
            <div class="bg-white rounded-3xl p-8 border border-slate-200 esk-card-hover">
                <div class="text-2xl mb-3">📅</div>
                <div class="text-lg text-blue-600 font-bold mb-2"><?= __('features.parent.calendar') ?></div>
                <p class="text-sm text-slate-600 leading-relaxed"><?= __('features.parent.calendar_desc') ?></p>
            </div>
        </div>
    </div>

    <div class="mt-16 bg-slate-50 border border-slate-200 rounded-3xl p-8 md:p-10">
        <h2 class="text-2xl md:text-3xl font-extrabold text-center"><?= __('features.deploy_heading') ?></h2>
        <p class="text-slate-500 text-center mt-2 mb-8"><?= __('features.deploy_sub') ?></p>
        <div class="grid md:grid-cols-3 gap-6 text-center">
            <a href="/products/app" class="bg-white rounded-2xl border border-slate-200 p-7 esk-card-hover block">
                <div class="text-xl font-bold text-blue-700"><?= __('pricing.app_section') ?></div>
                <p class="text-sm text-slate-500 mt-2"><?= __('features.deploy_app') ?></p>
                <div class="mt-4 text-sm font-semibold text-blue-600"><?= __('features.deploy_cta') ?> →</div>
            </a>
            <a href="/products/php" class="bg-white rounded-2xl border border-slate-200 p-7 esk-card-hover block">
                <div class="text-xl font-bold text-blue-700"><?= __('pricing.php_section') ?></div>
                <p class="text-sm text-slate-500 mt-2"><?= __('features.deploy_php') ?></p>
                <div class="mt-4 text-sm font-semibold text-blue-600"><?= __('features.deploy_cta') ?> →</div>
            </a>
            <a href="/products/theme" class="bg-white rounded-2xl border border-slate-200 p-7 esk-card-hover block">
                <div class="text-xl font-bold text-blue-700"><?= __('pricing.theme_section') ?></div>
                <p class="text-sm text-slate-500 mt-2"><?= __('features.deploy_theme') ?></p>
                <div class="mt-4 text-sm font-semibold text-blue-600"><?= __('features.deploy_cta') ?> →</div>
            </a>
        </div>
    </div>
</div>

<?php \App\Core\View::partial('site.partials.cta_band'); ?>