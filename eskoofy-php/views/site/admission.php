<?php $pageTitle = 'Online Admission'; ?>
<?php ob_start(); ?>

<section class="bg-blue-600 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">Online Admission</h1>
        <p class="text-blue-100 text-lg">Apply for admission to our school</p>
    </div>
</section>

<section class="py-16">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <form action="/admission" method="POST" enctype="multipart/form-data" class="space-y-8">
            <?= csrf_field() ?>

            <div class="bg-white rounded-xl shadow-sm p-8">
                <h2 class="text-xl font-bold mb-6 flex items-center">
                    <span class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm mr-3">1</span>
                    Student Information
                </h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                        <input type="text" name="first_name" value="<?= old('first_name') ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                        <input type="text" name="last_name" value="<?= old('last_name') ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth *</label>
                        <input type="date" name="date_of_birth" value="<?= old('date_of_birth') ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gender *</label>
                        <select name="gender" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Gender</option>
                            <option value="male" <?= old('gender') == 'male' ? 'selected' : '' ?>>Male</option>
                            <option value="female" <?= old('gender') == 'female' ? 'selected' : '' ?>>Female</option>
                            <option value="other" <?= old('gender') == 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="tel" name="phone" value="<?= old('phone') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" value="<?= old('email') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address *</label>
                        <textarea name="address" rows="2" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"><?= old('address') ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Class Applying For *</label>
                        <select name="class_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Class</option>
                            <?php if (!empty($classes)): ?>
                                <?php foreach ($classes as $class): ?>
                                <option value="<?= e($class->id) ?>" <?= old('class_id') == $class->id ? 'selected' : '' ?>><?= e($class->name) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Photo</label>
                        <input type="file" name="photo" accept="image/*" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-8">
                <h2 class="text-xl font-bold mb-6 flex items-center">
                    <span class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm mr-3">2</span>
                    Parent/Guardian Information
                </h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Father's Name *</label>
                        <input type="text" name="father_name" value="<?= old('father_name') ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Father's Phone *</label>
                        <input type="tel" name="father_phone" value="<?= old('father_phone') ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Father's Occupation</label>
                        <input type="text" name="father_occupation" value="<?= old('father_occupation') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mother's Name *</label>
                        <input type="text" name="mother_name" value="<?= old('mother_name') ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mother's Phone</label>
                        <input type="tel" name="mother_phone" value="<?= old('mother_phone') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mother's Occupation</label>
                        <input type="text" name="mother_occupation" value="<?= old('mother_occupation') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-8">
                <h2 class="text-xl font-bold mb-6 flex items-center">
                    <span class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm mr-3">3</span>
                    Previous School Information
                </h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Previous School Name</label>
                        <input type="text" name="previous_school" value="<?= old('previous_school') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Previous Class</label>
                        <input type="text" name="previous_class" value="<?= old('previous_class') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Previous Result/GPA</label>
                        <input type="text" name="previous_result" value="<?= old('previous_result') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Transfer Certificate</label>
                        <input type="file" name="transfer_certificate" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-8">
                <h2 class="text-xl font-bold mb-6 flex items-center">
                    <span class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm mr-3">4</span>
                    Document Upload
                </h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Birth Certificate</label>
                        <input type="file" name="birth_certificate" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Report Card</label>
                        <input type="file" name="report_card" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    </div>
                </div>
            </div>

            <div class="text-center">
                <button type="submit" class="bg-blue-600 text-white px-12 py-3 rounded-lg font-semibold hover:bg-blue-700 transition">Submit Application</button>
            </div>
        </form>
    </div>
</section>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>