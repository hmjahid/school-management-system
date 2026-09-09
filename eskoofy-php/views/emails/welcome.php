<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 8px;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #2563eb;"><?= e(config('school.name', 'School')) ?></h1>
        </div>
        <h2 style="color: #333;">Welcome, <?= e($user->name ?? '') ?>!</h2>
        <p style="color: #666; line-height: 1.6;">
            Your account has been created successfully. You can now log in to access the school management system.
        </p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="<?= e(url('/login')) ?>" style="background-color: #2563eb; color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold;">Login Now</a>
        </div>
        <p style="color: #999; font-size: 12px; text-align: center;">
            &copy; <?= date('Y') ?> <?= e(config('school.name', 'School')) ?>. All rights reserved.
        </p>
    </div>
</body>
</html>