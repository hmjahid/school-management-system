<footer class="bg-gray-800 text-white mt-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <h3 class="text-lg font-bold mb-4"><?= e(config('school.name', 'School')) ?></h3>
                <p class="text-gray-400 text-sm"><?= e(config('school.description', '')) ?></p>
            </div>
            <div>
                <h4 class="text-lg font-bold mb-4">Quick Links</h4>
                <ul class="space-y-2 text-gray-400 text-sm">
                    <li><a href="/about" class="hover:text-white">About Us</a></li>
                    <li><a href="/news" class="hover:text-white">News</a></li>
                    <li><a href="/events" class="hover:text-white">Events</a></li>
                    <li><a href="/gallery" class="hover:text-white">Gallery</a></li>
                    <li><a href="/contact" class="hover:text-white">Contact</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-lg font-bold mb-4">For Students</h4>
                <ul class="space-y-2 text-gray-400 text-sm">
                    <li><a href="/results" class="hover:text-white">Results</a></li>
                    <li><a href="/routine" class="hover:text-white">Routine</a></li>
                    <li><a href="/admission" class="hover:text-white">Admission</a></li>
                    <li><a href="/payments" class="hover:text-white">Payments</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-lg font-bold mb-4">Contact Info</h4>
                <ul class="space-y-2 text-gray-400 text-sm">
                    <li class="flex items-center">
                        <span class="mr-2">📍</span>
                        <?= e(config('school.address', '')) ?>
                    </li>
                    <li class="flex items-center">
                        <span class="mr-2">📞</span>
                        <?= e(config('school.phone', '')) ?>
                    </li>
                    <li class="flex items-center">
                        <span class="mr-2">✉️</span>
                        <?= e(config('school.email', '')) ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="border-t border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center text-gray-400 text-sm">
            &copy; <?= date('Y') ?> <?= e(config('school.name', 'School')) ?>. All rights reserved.
        </div>
    </div>
</footer>