<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gateways = [
            // bKash - Mobile Financial Service
            [
                'name' => 'bKash',
                'code' => 'bkash',
                'type' => 'mobile_financial_service',
                'is_active' => true,
                'is_online' => true,
                'has_api' => true,
                'test_mode' => true,
                'api_key' => 'test_api_key',
                'api_secret' => 'test_api_secret',
                'username' => 'test_username',
                'password' => 'test_password',
                'sandbox_url' => 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized',
                'live_url' => 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized',
                'callback_url' => url('/api/payments/bkash/callback'),
                'webhook_url' => url('/api/payments/bkash/webhook'),
                'success_url' => url('/payments/success'),
                'cancel_url' => url('/payments/cancel'),
                'ipn_url' => url('/api/payments/bkash/ipn'),
                'logo' => 'gateways/bkash.png',
                'description' => 'bKash is a leading mobile financial service in Bangladesh',
                'instructions' => '1. Dial *247# to open bKash menu\n2. Select "Payment"\n3. Enter Merchant bKash Account: 017XXXXXXXX\n4. Enter Amount\n5. Enter Reference: Your Invoice Number\n6. Enter Counter: 1\n7. Enter your bKash Mobile Menu PIN to confirm',
                'currency' => 'BDT',
                'fee_percentage' => 1.85,
                'fee_fixed' => 10,
                'min_amount' => 10,
                'max_amount' => 50000,
                'supported_currencies' => ['BDT'],
                'sort_order' => 1,
            ],
            // Nagad - Mobile Financial Service
            [
                'name' => 'Nagad',
                'code' => 'nagad',
                'type' => 'mobile_financial_service',
                'is_active' => true,
                'is_online' => true,
                'has_api' => true,
                'test_mode' => true,
                'api_key' => 'test_api_key',
                'api_secret' => 'test_api_secret',
                'sandbox_url' => 'https://sandbox.mynagad.com:10080/remote-payment-gateway-1.0/api/dfs',
                'live_url' => 'https://api.mynagad.com/api/dfs',
                'callback_url' => url('/api/payments/nagad/callback'),
                'webhook_url' => url('/api/payments/nagad/webhook'),
                'success_url' => url('/payments/success'),
                'cancel_url' => url('/payments/cancel'),
                'ipn_url' => url('/api/payments/nagad/ipn'),
                'logo' => 'gateways/nagad.png',
                'description' => 'Nagad is a leading mobile financial service in Bangladesh',
                'instructions' => '1. Dial *167# to open Nagad menu\n2. Select "Payment"\n3. Enter Merchant Nagad Account: 017XXXXXXXX\n4. Enter Amount\n5. Enter Reference: Your Invoice Number\n6. Enter your Nagad Mobile Menu PIN to confirm',
                'currency' => 'BDT',
                'fee_percentage' => 1.4,
                'fee_fixed' => 10,
                'min_amount' => 10,
                'max_amount' => 50000,
                'supported_currencies' => ['BDT'],
                'sort_order' => 2,
            ],
            // Rocket - Mobile Financial Service
            [
                'name' => 'Rocket',
                'code' => 'rocket',
                'type' => 'mobile_financial_service',
                'is_active' => true,
                'is_online' => true,
                'has_api' => true,
                'test_mode' => true,
                'api_key' => 'test_api_key',
                'api_secret' => 'test_api_secret',
                'sandbox_url' => 'https://sandbox.rocket.com.bd/api/v1',
                'live_url' => 'https://api.rocket.com.bd/api/v1',
                'callback_url' => url('/api/payments/rocket/callback'),
                'webhook_url' => url('/api/payments/rocket/webhook'),
                'success_url' => url('/payments/success'),
                'cancel_url' => url('/payments/cancel'),
                'ipn_url' => url('/api/payments/rocket/ipn'),
                'logo' => 'gateways/rocket.png',
                'description' => 'Rocket is a mobile financial service by Dutch-Bangla Bank',
                'instructions' => '1. Dial *322# to open Rocket menu\n2. Select "Payment"\n3. Enter Biller ID: SCHOOL\n4. Enter Bill Number: Your Invoice Number\n5. Enter Amount\n6. Enter your Rocket Mobile Menu PIN to confirm',
                'currency' => 'BDT',
                'fee_percentage' => 1.2,
                'fee_fixed' => 10,
                'min_amount' => 10,
                'max_amount' => 50000,
                'supported_currencies' => ['BDT'],
                'sort_order' => 3,
            ],
            // Cash - Offline Payment
            [
                'name' => 'Cash',
                'code' => 'cash',
                'type' => 'other',
                'is_active' => true,
                'is_online' => false,
                'has_api' => false,
                'description' => 'Pay with cash at the school office',
                'instructions' => 'Please visit the school office to make a cash payment during office hours.',
                'currency' => 'BDT',
                'fee_percentage' => 0,
                'fee_fixed' => 0,
                'sort_order' => 10,
            ],
            // Bank Transfer - Offline Payment
            [
                'name' => 'Bank Transfer',
                'code' => 'bank_transfer',
                'type' => 'bank',
                'is_active' => true,
                'is_online' => false,
                'has_api' => false,
                'description' => 'Transfer money directly to our bank account',
                'instructions' => "Bank Name: Example Bank\nAccount Name: Your School Name\nAccount Number: 1234567890123\nBranch: Main Branch\nRouting Number: 123456789\n\nPlease include your invoice number as the payment reference.",
                'currency' => 'BDT',
                'fee_percentage' => 0,
                'fee_fixed' => 0,
                'sort_order' => 11,
            ],
            // Cheque - Offline Payment
            [
                'name' => 'Cheque',
                'code' => 'cheque',
                'type' => 'bank',
                'is_active' => true,
                'is_online' => false,
                'has_api' => false,
                'description' => 'Pay by cheque at the school office',
                'instructions' => 'Please make the cheque payable to "Your School Name" and submit it to the school office during office hours. Include your invoice number on the back of the cheque.',
                'currency' => 'BDT',
                'fee_percentage' => 0,
                'fee_fixed' => 0,
                'sort_order' => 12,
            ],
        ];

        foreach ($gateways as $gateway) {
            // Extract extra attributes that are not direct columns
            $extraAttributes = [];
            $columns = [
                'name', 'code', 'type', 'is_active', 'is_online', 'has_api', 'test_mode',
                'api_key', 'api_secret', 'api_username', 'api_password', 'sandbox_url', 'live_url',
                'callback_url', 'webhook_url', 'success_url', 'cancel_url', 'ipn_url', 'logo',
                'description', 'instructions', 'currency', 'fee_percentage', 'fee_fixed',
                'min_amount', 'max_amount', 'supported_currencies', 'sort_order'
            ];
            
            foreach ($gateway as $key => $value) {
                if (!in_array($key, $columns)) {
                    $extraAttributes[$key] = $value;
                    unset($gateway[$key]);
                }
            }
            
            // Add extra attributes to the gateway
            if (!empty($extraAttributes)) {
                $gateway['extra_attributes'] = $extraAttributes;
            }
            
            // Create or update the gateway
            PaymentGateway::updateOrCreate(
                ['code' => $gateway['code']],
                $gateway
            );
        }

        $this->command->info('Payment gateways seeded successfully!');
    }
}
