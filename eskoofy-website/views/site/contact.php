<?php $title = __('nav.contact'); $siteTitle = $title; ?>

<section class="max-w-3xl mx-auto px-4 py-16">
    <h1 class="text-4xl font-extrabold mb-2"><?= __('contact.title') ?></h1>
    <p class="text-slate-500 mb-8"><?= __('contact.sub') ?></p>

    <form method="post" action="/contact" class="bg-white rounded-2xl border border-slate-200 p-8 space-y-4">
            <?= csrf_field() ?>
        <div>
            <label class="block text-sm font-semibold mb-1"><?= __('contact.name') ?></label>
            <input name="name" required maxlength="120" class="w-full border border-slate-300 rounded-lg px-4 py-2">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1"><?= __('contact.email') ?></label>
            <input type="email" name="email" required maxlength="191" class="w-full border border-slate-300 rounded-lg px-4 py-2">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1"><?= __('contact.subject') ?></label>
            <input name="subject" maxlength="191" class="w-full border border-slate-300 rounded-lg px-4 py-2">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1"><?= __('contact.message') ?></label>
            <textarea name="message" required rows="5" maxlength="4000" class="w-full border border-slate-300 rounded-lg px-4 py-2"></textarea>
        </div>
        <button type="submit" class="bg-slate-900 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold"><?= __('contact.send') ?></button>
    </form>
</section>