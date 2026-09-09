<header class="bg-white shadow-sm sticky top-0 z-50">
    <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <a href="/" class="flex items-center">
                    <span class="text-xl font-bold text-blue-600"><?= e(config('school.name', 'School')) ?></span>
                </a>
            </div>
            <div class="hidden md:flex items-center space-x-8">
                <a href="/" class="text-gray-700 hover:text-blue-600"><?= e(site_ui('nav.home')) ?></a>
                <a href="/about" class="text-gray-700 hover:text-blue-600"><?= e(site_ui('nav.about')) ?></a>
                <a href="/news" class="text-gray-700 hover:text-blue-600"><?= e(site_ui('nav.news')) ?></a>
                <a href="/events" class="text-gray-700 hover:text-blue-600"><?= e(site_ui('nav.events')) ?></a>
                <a href="/gallery" class="text-gray-700 hover:text-blue-600"><?= e(site_ui('nav.gallery')) ?></a>
                <a href="/contact" class="text-gray-700 hover:text-blue-600"><?= e(site_ui('nav.contact')) ?></a>
                <a href="/results" class="text-gray-700 hover:text-blue-600"><?= e(site_ui('nav.results')) ?></a>
                <a href="/admission" class="text-gray-700 hover:text-blue-600"><?= e(site_ui('nav.admission')) ?></a>
                <a href="/login" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"><?= e(site_ui('nav.login')) ?></a>
            </div>
            <div class="md:hidden flex items-center">
                <button id="mobile-menu-btn" class="text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>
    </nav>
    <div id="mobile-menu" class="hidden md:hidden">
        <div class="px-4 py-2 space-y-1">
            <a href="/" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded"><?= e(site_ui('nav.home')) ?></a>
            <a href="/about" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded"><?= e(site_ui('nav.about')) ?></a>
            <a href="/news" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded"><?= e(site_ui('nav.news')) ?></a>
            <a href="/events" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded"><?= e(site_ui('nav.events')) ?></a>
            <a href="/gallery" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded"><?= e(site_ui('nav.gallery')) ?></a>
            <a href="/contact" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded"><?= e(site_ui('nav.contact')) ?></a>
            <a href="/results" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded"><?= e(site_ui('nav.results')) ?></a>
            <a href="/admission" class="block px-3 py-2 text-gray-700 hover:bg-gray-100 rounded"><?= e(site_ui('nav.admission')) ?></a>
            <a href="/login" class="block px-3 py-2 bg-blue-600 text-white rounded text-center"><?= e(site_ui('nav.login')) ?></a>
        </div>
    </div>
</header>