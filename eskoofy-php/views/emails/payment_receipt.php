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
            <h2 style="color: #333;">Payment Receipt</h2>
        </div>

        <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
            <h3 style="color: #333; margin-top: 0;">Transaction Details</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; color: #666;">Receipt No:</td>
                    <td style="padding: 8px 0; color: #333; font-weight: bold;"><?= e($payment['receipt_number'] ?? '') ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666;">Date:</td>
                    <td style="padding: 8px 0; color: #333;"><?= e($payment['created_at'] ?? '') ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666;">Student:</td>
                    <td style="padding: 8px 0; color: #333;"><?= e($payment['student_name'] ?? '') ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666;">Fee Type:</td>
                    <td style="padding: 8px 0; color: #333;"><?= e($payment['fee_name'] ?? '') ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666;">Amount:</td>
                    <td style="padding: 8px 0; color: #22c55e; font-weight: bold;"><?= e(format_currency((float)($payment['amount'] ?? 0))) ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666;">Method:</td>
                    <td style="padding: 8px 0; color: #333;"><?= e($payment['payment_method'] ?? $payment['method'] ?? '') ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #666;">Status:</td>
                    <td style="padding: 8px 0; color: #22c55e; font-weight: bold;">Paid</td>
                </tr>
            </table>
        </div>

        <p style="color: #999; font-size: 12px; text-align: center;">
            This is a computer-generated receipt. No signature required.<br>
            &copy; <?= date('Y') ?> <?= e(config('school.name', 'School')) ?>. All rights reserved.
        </p>
    </div>
</body>
</html>