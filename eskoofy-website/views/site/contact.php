<?php $title = __('nav.contact'); $siteTitle = $title; ?>

<section class="esk-hero text-white">
    <div class="relative z-10 max-w-7xl mx-auto px-4 py-16 text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold"><?= __('contact.title') ?></h1>
        <p class="mt-4 text-slate-300 text-lg max-w-2xl mx-auto"><?= __('contact.sub') ?></p>
    </div>
</section>

<section class="max-w-3xl mx-auto px-4 py-16">
    <form method="post" action="/contact" class="bg-white rounded-3xl border border-slate-200 p-8 space-y-4 shadow-sm">
            <?= csrf_field() ?>
        <div>
            <label class="block text-sm font-semibold mb-1"><?= __('contact.name') ?></label>
            <input name="name" required maxlength="120" class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1"><?= __('contact.email') ?></label>
            <input type="email" name="email" required maxlength="191" class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1"><?= __('contact.subject') ?></label>
            <input name="subject" maxlength="191" class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1"><?= __('contact.message') ?></label>
            <textarea name="message" required rows="5" maxlength="4000" class="w-full border border-slate-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
        </div>
        <button type="submit" class="esk-btn-primary bg-blue-600 hover:bg-blue-500 text-white px-6 py-3 rounded-xl font-semibold"><?= __('contact.send') ?></button>
    </form>
</section>