<section class="max-w-7xl mx-auto px-4 pb-16">
    <div class="rounded-3xl bg-slate-900 text-white p-8 md:p-12">
        <div class="max-w-3xl">
            <span class="text-xs uppercase tracking-widest text-blue-300 font-semibold"><?= __('services.tag') ?></span>
            <h2 class="text-3xl md:text-4xl font-extrabold mt-3"><?= __('services.section_heading') ?></h2>
            <p class="mt-4 text-slate-300 leading-relaxed"><?= __('services.intro') ?></p>
        </div>
        <div class="mt-8 grid md:grid-cols-2 gap-6">
            <div class="bg-white/5 border border-white/10 rounded-2xl p-7">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <h3 class="font-bold text-lg"><?= __('services.dep_t') ?></h3>
                    <span class="text-xs font-semibold text-emerald-300 bg-emerald-400/10 border border-emerald-400/20 px-3 py-1 rounded-full"><?= __('services.dep_price') ?></span>
                </div>
                <p class="mt-3 text-sm text-slate-300 leading-relaxed"><?= __('services.dep_d') ?></p>
            </div>
            <div class="bg-white/5 border border-white/10 rounded-2xl p-7">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <h3 class="font-bold text-lg"><?= __('services.care_t') ?></h3>
                    <span class="text-xs font-semibold text-emerald-300 bg-emerald-400/10 border border-emerald-400/20 px-3 py-1 rounded-full"><?= __('services.care_price') ?></span>
                </div>
                <p class="mt-3 text-sm text-slate-300 leading-relaxed"><?= __('services.care_d') ?></p>
            </div>
        </div>
        <div class="mt-8 flex flex-wrap items-center gap-4">
            <a href="/contact" class="esk-btn-primary bg-blue-600 hover:bg-blue-500 px-7 py-3.5 rounded-xl font-semibold inline-flex items-center gap-2"><?= __('services.cta') ?></a>
            <span class="text-xs text-slate-400 max-w-xl"><?= __('services.bill_note') ?></span>
        </div>
    </div>
</section>