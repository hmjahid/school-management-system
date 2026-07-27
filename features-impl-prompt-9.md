# Features Implementation Prompt — Batch 9

## Task 1: Contact Form Success Popup with Thank Message

### Problem
After successful contact form submission, the user is redirected back to the contact page with a flash `status` message. This is currently shown as a small toast notification at the top of the page, which is easy to miss. The user wants a nice, prominent popup/modal with a thank-you message.

### Current Flow
1. `SitePageController@contactStore` validates and saves the submission
2. Redirects to `site.contact` with `->with('status', __('Thank you — we will get back to you soon.'))`
3. `layouts/app.blade.php` line 126-127 shows this as a flash toast via `data-flash-toast`
4. `app.js` picks up `data-flash-toast` elements and calls `showToast()`

### Implementation

#### Option A: Dedicated success modal on contact page (Recommended)
Add a Blade-rendered success modal that appears when `session('status')` is present on the contact page. This gives a nicer, more prominent thank-you experience than a fleeting toast.

1. **In `resources/views/site/contact.blade.php`**, add a success modal block at the top of `@section('content')`:
   ```blade
   @if(session('status'))
   <div id="contact-success-modal" class="fixed inset-0 z-[100] flex items-center justify-center" role="dialog" aria-modal="true">
       <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
       <div class="relative mx-4 w-full max-w-md rounded-2xl bg-white p-8 text-center shadow-2xl">
           <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
               <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
           </div>
           <h2 class="mt-4 text-xl font-bold text-slate-900">{{ __('Thank You!') }}</h2>
           <p class="mt-2 text-sm text-slate-600">{{ session('status') }}</p>
           <button onclick="this.closest('#contact-success-modal').remove()" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-blue-700">
               {{ __('OK') }}
           </button>
       </div>
   </div>
   @endif
   ```

2. **Remove the toast** for this specific flash on the contact page so both don't show simultaneously. The existing flash toast in `layouts/app.blade.php` will still fire, but the modal is more prominent. To suppress the toast on the contact page when the success modal shows, either:
   - Use a different flash key (e.g., `contact_success`) instead of `status`
   - Or keep both (toast is small, modal is big — both are fine UX)

   **Best approach**: Use a separate flash key `contact_success` in the controller, and check for it in the contact page template. This avoids conflicts with other uses of `status`.

3. **Controller change** in `SitePageController@contactStore`:
   ```php
   return redirect()->route('site.contact')->with('contact_success', __('Thank you — we will get back to you soon.'));
   ```

4. **Contact page** checks for `session('contact_success')` instead of `session('status')`.

5. **Add close-on-backdrop-click** and **close-on-ESC** behavior via inline `<script>`.

### Files to Modify
- `app/Http/Controllers/Web/SitePageController.php` — change flash key to `contact_success`
- `resources/views/site/contact.blade.php` — add success modal markup + close behavior

---

## Task 2: Add PWA Install Option in Dashboard

### Problem
The PWA service worker and manifest are set up, but there's no UI to trigger the install prompt. The `beforeinstallprompt` event is not captured, and no "Install App" button exists anywhere. Users on mobile/desktop have no way to know they can install the app.

### How PWA Install Works
1. Browser fires `beforeinstallprompt` event when the app is installable
2. We save the event and call `prompt()` later when the user clicks "Install"
3. After install, the event is consumed and the button can be hidden

### Implementation

#### 2a. Capture the install prompt globally
Add to `resources/js/app.js` (or inline in the layout) — capture `beforeinstallprompt`:

```js
window.__deferredInstallPrompt = null;
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    window.__deferredInstallPrompt = e;
});
```

#### 2b. Add "Install App" button to dashboard topbar
In `resources/views/partials/dashboard/topbar.blade.php`, add an install button that:
- Only shows when the install prompt is available
- Triggers the browser install prompt on click
- Hides itself after install

```html
<button type="button" id="pwa-install-btn" class="hidden items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200" title="Install App">
    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
    <span class="hidden md:inline">Install App</span>
</button>
```

Add JS to show/handle the button:
```js
window.addEventListener('load', () => {
    const btn = document.getElementById('pwa-install-btn');
    if (!btn) return;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        window.__deferredInstallPrompt = e;
        btn.classList.remove('hidden');
        btn.classList.add('inline-flex');
    });
    btn.addEventListener('click', async () => {
        if (!window.__deferredInstallPrompt) return;
        window.__deferredInstallPrompt.prompt();
        const result = await window.__deferredInstallPrompt.userChoice;
        if (result.outcome === 'accepted') {
            btn.classList.add('hidden');
        }
        window.__deferredInstallPrompt = null;
    });
    window.addEventListener('appinstalled', () => {
        btn.classList.add('hidden');
        window.__deferredInstallPrompt = null;
    });
});
```

#### 2c. Add "Install App" option to dashboard sidebar or settings
Optionally, add a visible "Install App" entry in the sidebar under the user section, or on the dashboard home page as a card/banner for mobile users.

**Recommended**: Add a dismissible install banner on the dashboard index page that shows only on mobile (where install is most useful).

### Files to Modify
- `resources/js/app.js` — capture `beforeinstallprompt` event
- `resources/views/partials/dashboard/topbar.blade.php` — add install button
- `resources/views/dashboard/index.blade.php` — optionally add install banner for mobile

---

## Verification Checklist
- [ ] Contact form submission shows a nice modal popup with thank-you message
- [ ] Modal can be closed by clicking OK button, backdrop, or pressing ESC
- [ ] Toast notification does not duplicate the modal message
- [ ] Dashboard topbar shows "Install App" button when PWA is installable
- [ ] Clicking "Install App" triggers the browser's native install prompt
- [ ] Button hides after successful installation
- [ ] No console errors related to PWA or the modal
