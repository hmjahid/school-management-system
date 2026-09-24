<?php
$title = __('nav.contact');
$siteTitle = $title;
$seo = ['title' => __('nav.contact'), 'description' => __('contact.sub'), 'canonical' => '/contact'];

$c = \App\Models\Settings::all();
$cSupport = (string) ($c['site.support_email'] ?? $c['site.contact_email'] ?? '');
$cSales = (string) ($c['site.sales_email'] ?? '');
$cPhone = (string) ($c['site.support_phone'] ?? '');
$cWhatsappRaw = (string) ($c['site.whatsapp'] ?? '');
$cWhatsapp = preg_replace('/[^0-9]/', '', $cWhatsappRaw);
$cAddress = (string) ($c['site.address'] ?? '');
$cHours = (string) ($c['site.support_hours'] ?? '');
$cBrand = (string) ($c['site.name'] ?? 'Eskoofy');

$channels = array_values(array_filter([
    $cSales !== '' ? ['mail', __('contact.email_sales'), $cSales, 'mailto:' . $cSales, false] : null,
    $cSupport !== '' ? ['mail', __('contact.email_support'), $cSupport, 'mailto:' . $cSupport, false] : null,
    $cPhone !== '' ? ['phone', __('contact.phone'), $cPhone, 'tel:' . preg_replace('/[^0-9+]/', '', $cPhone), false] : null,
    $cWhatsapp !== '' ? ['chat', __('contact.whatsapp'), $cWhatsappRaw, 'https://wa.me/' . $cWhatsapp, true] : null,
]));
$cmsHero = !empty($cmsPage['heading']) || !empty($cmsPage['intro']);
?>

<?php if (! $cmsHero): ?>
<section class="esk-hero text-white">
    <div class="relative z-10 max-w-7xl mx-auto px-4 py-16 text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold"><?= __('contact.title') ?></h1>
        <p class="mt-4 text-slate-300 text-lg max-w-2xl mx-auto"><?= __('contact.sub') ?></p>
        <div class="mt-4 inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-xs font-medium text-slate-200">
            <span class="inline-block h-2 w-2 rounded-full bg-green-400" aria-hidden="true"></span>
            <?= __('contact.response_time_value') ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php \App\Core\View::partial('site.partials.cms_block', ['cmsPage' => $cmsPage ?? null]); ?>

<section class="max-w-7xl mx-auto px-4 py-16">
    <div class="grid lg:grid-cols-5 gap-8">
        <!-- Contact details -->
        <div class="lg:col-span-2 space-y-4">
            <h2 class="text-xl font-bold text-slate-900"><?= __('contact.info_heading') ?></h2>

            <?php if (!empty($channels)): ?>
                <div class="grid sm:grid-cols-2 gap-3">
                    <?php foreach ($channels as [$icon, $label, $value, $href, $external]): ?>
                        <a href="<?= htmlspecialchars($href) ?>"<?= $external ? ' target="_blank" rel="noopener noreferrer"' : '' ?> class="esk-card-hover block rounded-xl border border-slate-200 bg-white p-4">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
                                <?php if ($icon === 'phone'): ?>
                                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z"/></svg>
                                <?php elseif ($icon === 'chat'): ?>
                                    <svg viewBox="0 0 24 24" class="h-4 w-4 text-green-500" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8 8 0 1 1 12 20Z"/></svg>
                                <?php else: ?>
                                    <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 6 10-6"/></svg>
                                <?php endif; ?>
                                <?= htmlspecialchars($label) ?>
                            </div>
                            <div class="mt-1.5 text-sm font-semibold text-slate-700 break-all"><?= htmlspecialchars($value) ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-3 text-sm">
                <?php if ($cAddress !== ''): ?>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400"><?= __('contact.address') ?></div>
                        <div class="mt-1 text-slate-700"><?= htmlspecialchars($cAddress) ?></div>
                        <a class="mt-1.5 inline-block text-xs font-semibold text-blue-600 hover:underline" href="https://www.openstreetmap.org/search?query=<?= urlencode($cAddress) ?>" target="_blank" rel="noopener noreferrer"><?= __('contact.directions') ?> →</a>
                    </div>
                <?php endif; ?>
                <?php if ($cHours !== ''): ?>
                    <div class="pt-3 border-t border-slate-100">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400"><?= __('contact.hours') ?></div>
                        <div class="mt-1 text-slate-700"><?= htmlspecialchars($cHours) ?></div>
                    </div>
                <?php endif; ?>
                <div class="pt-3 border-t border-slate-100">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400"><?= __('contact.response_time') ?></div>
                    <div class="mt-1 text-slate-700"><?= __('contact.response_time_value') ?></div>
                </div>
            </div>

            <div class="rounded-xl bg-slate-900 text-slate-300 p-5 text-sm">
                <div class="font-semibold text-white mb-1"><?= __('contact.help_heading') ?></div>
                <p class="text-slate-400"><?= __('contact.help_sub') ?></p>
                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                    <a href="/features" class="rounded-lg bg-slate-800 hover:bg-slate-700 px-3 py-1.5"><?= __('support.link_help') ?></a>
                    <a href="/pricing" class="rounded-lg bg-slate-800 hover:bg-slate-700 px-3 py-1.5"><?= __('support.link_pricing') ?></a>
                    <a href="/compare" class="rounded-lg bg-slate-800 hover:bg-slate-700 px-3 py-1.5"><?= __('support.link_compare') ?></a>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="lg:col-span-3">
            <div class="bg-white rounded-3xl border border-slate-200 p-6 md:p-8 shadow-sm">
                <h2 class="text-xl font-bold text-slate-900 mb-1"><?= __('contact.form_heading') ?></h2>
                <p class="text-sm text-slate-500 mb-5"><?= __('contact.form_sub') ?></p>
                <form method="post" action="/contact" class="space-y-4">
                    <?= csrf_field() ?>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label for="contact-name" class="block text-sm font-semibold mb-1"><?= __('contact.name') ?></label>
                            <input id="contact-name" name="name" required maxlength="120" value="<?= old('name') ?>" class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="contact-email" class="block text-sm font-semibold mb-1"><?= __('contact.email') ?></label>
                            <input id="contact-email" type="email" name="email" required maxlength="191" value="<?= old('email') ?>" class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label for="contact-topic" class="block text-sm font-semibold mb-1"><?= __('contact.topic') ?></label>
                            <select id="contact-topic" name="topic" class="w-full border border-slate-300 rounded-lg px-4 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="Sales"><?= __('contact.topic_sales') ?></option>
                                <option value="Support"><?= __('contact.topic_support') ?></option>
                                <option value="Billing"><?= __('contact.topic_billing') ?></option>
                                <option value="Other"><?= __('contact.topic_other') ?></option>
                            </select>
                        </div>
                        <div>
                            <label for="contact-subject" class="block text-sm font-semibold mb-1"><?= __('contact.subject') ?></label>
                            <input id="contact-subject" name="subject" maxlength="191" value="<?= old('subject') ?>" class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <div>
                        <label for="contact-message" class="block text-sm font-semibold mb-1"><?= __('contact.message') ?></label>
                        <textarea id="contact-message" name="message" required rows="6" maxlength="4000" class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= old('message') ?></textarea>
                        <p class="mt-1 text-xs text-slate-400"><?= __('contact.privacy_note', ['brand' => $cBrand]) ?></p>
                    </div>
                    <button type="submit" class="esk-btn-primary bg-blue-600 hover:bg-blue-500 text-white px-6 py-3 rounded-xl font-semibold"><?= __('contact.send') ?></button>
                </form>
            </div>

            <!-- FAQ -->
            <div class="mt-6 bg-white rounded-3xl border border-slate-200 p-6 md:p-8">
                <h2 class="text-lg font-bold text-slate-900 mb-4"><?= __('contact.faq_heading') ?></h2>
                <div class="space-y-3">
                    <?php for ($i = 1; $i <= 3; $i++): ?>
                        <details class="group rounded-xl border border-slate-200 p-4">
                            <summary class="flex cursor-pointer items-center justify-between text-sm font-semibold text-slate-700 list-none">
                                <?= __('contact.faq_' . $i . 'q') ?>
                                <svg viewBox="0 0 20 20" class="h-4 w-4 transition group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 8 4 4 4-4"/></svg>
                            </summary>
                            <p class="mt-2 text-sm text-slate-500"><?= __('contact.faq_' . $i . 'a') ?></p>
                        </details>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
</section>
