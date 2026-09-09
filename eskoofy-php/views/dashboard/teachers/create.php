<?php $pageTitle = 'Add Teacher'; ?>
<?php ob_start(); ?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Add New Teacher</h1>
    <a href="/dashboard/teachers" class="text-gray-600 hover:text-gray-800">← Back to Teachers</a>
</div>

<form action="/dashboard/teachers" method="POST" enctype="multipart/form-data" class="space-y-6">
    <?= csrf_field() ?>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Personal Information</h2>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Teacher ID *</label>
                <input type="text" name="teacher_id" value="<?= old('teacher_id') ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                <input type="text" name="name" value="<?= old('name') ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                <input type="email" name="email" value="<?= old('email') ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
                <input type="tel" name="phone" value="<?= old('phone') ?>" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                <input type="date" name="date_of_birth" value="<?= old('date_of_birth') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                <select name="gender" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">Select</option>
                    <option value="male" <?= old('gender') == 'male' ? 'selected' : '' ?>>Male</option>
                    <option value="female" <?= old('gender') == 'female' ? 'selected' : '' ?>>Female</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Blood Group</label>
                <select name="blood_group" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">Select</option>
                    <?php foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg): ?>
                    <option value="<?= $bg ?>" <?= old('blood_group') == $bg ? 'selected' : '' ?>><?= $bg ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Photo</label>
                <input type="file" name="photo" accept="image/*" class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <textarea name="address" rows="2" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"><?= old('address') ?></textarea>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Academic Information</h2>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subject *</label>
                <select name="subject_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="">Select Subject</option>
                    <?php if (!empty($subjects)): ?>
                        <?php foreach ($subjects as $subject): ?>
                        <option value="<?= e($subject->id) ?>" <?= old('subject_id') == $subject->id ? 'selected' : '' ?>><?= e($subject->name) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                <input type="text" name="department" value="<?= old('department') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Qualification</label>
                <input type="text" name="qualification" value="<?= old('qualification') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Experience (years)</label>
                <input type="number" name="experience" value="<?= old('experience') ?>" min="0" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Joining Date</label>
                <input type="date" name="joining_date" value="<?= old('joining_date') ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Salary</label>
                <input type="number" name="salary" value="<?= old('salary') ?>" step="0.01" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-bold mb-4">Login Credentials</h2>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                <input type="password" name="password" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password *</label>
                <input type="password" name="password_confirmation" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
    </div>

    <div class="flex justify-end space-x-4">
        <a href="/dashboard/teachers" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">Save Teacher</button>
    </div>
</form>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../layouts/dashboard.php'; ?>