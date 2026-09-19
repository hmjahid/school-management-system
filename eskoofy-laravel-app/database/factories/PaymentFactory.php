<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition()
    {
        return [
            'paymentable_type' => User::class,
            'paymentable_id' => User::factory(),
            'invoice_number' => 'INV-'.$this->faker->unique()->numerify('########'),
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'paid_amount' => $this->faker->randomFloat(2, 100, 10000),
            'total_amount' => $this->faker->randomFloat(2, 100, 10000),
            'payment_method' => $this->faker->randomElement(['cash', 'bkash', 'nagad', 'bank_transfer']),
            'payment_status' => Payment::STATUS_COMPLETED,
            'payment_date' => now(),
            'transaction_id' => 'TXN-'.$this->faker->unique()->numerify('########'),
        ];
    }
}
