<?php

namespace Tests\Unit\Services;

use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RefundConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected RefundService $refundService;
    protected User $admin;
    protected User $user;
    protected Payment $payment;
    protected int $concurrentRequests = 5;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->user = User::factory()->create(['email' => 'user@example.com']);

        // Create a completed payment
        $this->payment = Payment::factory()->create([
            'user_id' => $this->user->id,
            'amount' => 1000.00,
            'currency' => 'BDT',
            'payment_status' => Payment::STATUS_COMPLETED,
            'payment_method' => 'test_gateway',
            'transaction_id' => 'TXN' . uniqid(),
        ]);

        $this->refundService = app(RefundService::class);
    }

    /** @test */
    public function it_handles_concurrent_refund_requests_safely()
    {
        // Simulate multiple concurrent refund requests
        $responses = [];
        $refundAmount = 500.00;
        $expectedSuccessful = 2; // Only 2 refunds of 500 should be possible for a 1000 payment

        // Create a barrier to make requests concurrent
        $barrier = $this->createBarrier($this->concurrentRequests);
        
        // Create concurrent requests
        for ($i = 0; $i < $this->concurrentRequests; $i++) {
            $responses[] = $this->asyncRefundRequest($barrier, $refundAmount, $i);
        }

        // Wait for all requests to complete
        $this->waitForResponses($responses);

        // Count successful responses
        $successfulRefunds = array_filter($responses, fn($response) => $response['success']);
        $failedRefunds = array_filter($responses, fn($response) => !$response['success']);

        // Assertions
        $this->assertCount($expectedSuccessful, $successfulRefunds, 
            "Expected exactly {$expectedSuccessful} successful refunds out of {$this->concurrentRequests} concurrent requests");
            
        $this->assertCount($this->concurrentRequests - $expectedSuccessful, $failedRefunds,
            "Expected " . ($this->concurrentRequests - $expectedSuccessful) . " failed refunds due to concurrency");

        // Verify total refunded amount doesn't exceed payment amount
        $totalRefunded = $this->payment->refunds()->sum('amount');
        $this->assertEquals(1000.00, $totalRefunded, 
            "Total refunded amount should not exceed the payment amount");

        // Verify payment status
        $this->assertEquals('fully_refunded', $this->payment->fresh()->refund_status,
            "Payment should be marked as fully refunded");
    }

    /** @test */
    public function it_handles_concurrent_partial_refunds_correctly()
    {
        // Simulate multiple concurrent partial refund requests
        $responses = [];
        $refundAmount = 200.00;
        $expectedSuccessful = 5; // 5 x 200 = 1000 (full amount)

        // Create a barrier to make requests concurrent
        $barrier = $this->createBarrier($expectedSuccessful);
        
        // Create concurrent requests
        for ($i = 0; $i < $expectedSuccessful; $i++) {
            $responses[] = $this->asyncRefundRequest($barrier, $refundAmount, $i);
        }

        // Wait for all requests to complete
        $this->waitForResponses($responses);

        // Count successful responses
        $successfulRefunds = array_filter($responses, fn($response) => $response['success']);
        $failedRefunds = array_filter($responses, fn($response) => !$response['success']);

        // Assertions
        $this->assertCount($expectedSuccessful, $successfulRefunds,
            "Expected {$expectedSuccessful} successful partial refunds");
            
        $this->assertCount(0, $failedRefunds,
            "Expected no failed refunds for non-overlapping partial amounts");

        // Verify total refunded amount equals payment amount
        $totalRefunded = $this->payment->refunds()->sum('amount');
        $this->assertEquals(1000.00, $totalRefunded,
            "Total refunded amount should equal the payment amount");
    }

    /** @test */
    public function it_prevents_double_processing_of_same_refund()
    {
        // Create a pending refund
        $refund = Refund::create([
            'payment_id' => $this->payment->id,
            'user_id' => $this->user->id,
            'processed_by' => $this->admin->id,
            'amount' => 500.00,
            'currency' => 'BDT',
            'status' => 'pending',
            'reason' => 'Test refund',
        ]);

        // Simulate multiple concurrent attempts to process the same refund
        $responses = [];
        $barrier = $this->createBarrier(2);
        
        $processRefund = function () use ($barrier, $refund, &$responses) {
            $barrier->wait();
            
            DB::beginTransaction();
            try {
                // Lock the refund row for update
                $refund = Refund::lockForUpdate()->find($refund->id);
                
                if ($refund->status === 'pending') {
                    // Simulate processing delay
                    usleep(100000); // 100ms
                    
                    $refund->update([
                        'status' => 'completed',
                        'transaction_id' => 'TXN' . uniqid(),
                        'processed_at' => now(),
                    ]);
                    
                    $responses[] = ['success' => true, 'message' => 'Refund processed'];
                } else {
                    $responses[] = ['success' => false, 'message' => 'Refund already processed'];
                }
                
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $responses[] = ['success' => false, 'message' => $e->getMessage()];
            }
        };

        // Create two concurrent processes
        $process1 = new \React\ChildProcess\Process('php -r "' . $this->getProcessCode($processRefund) . '"');
        $process2 = new \React\ChildProcess\Process('php -r "' . $this->getProcessCode($processRefund) . '"');
        
        $process1->start();
        $process2->start();
        
        // Wait for both processes to complete
        $this->waitForProcesses([$process1, $process2]);
        
        // Assertions
        $successful = array_filter($responses, fn($r) => $r['success']);
        $failed = array_filter($responses, fn($r) => !$r['success']);
        
        $this->assertCount(1, $successful, 'Only one process should successfully process the refund');
        $this->assertCount(1, $failed, 'One process should fail to process the refund');
        $this->assertStringContainsString('already processed', $failed[0]['message']);
    }

    /**
     * Helper to create a barrier for synchronizing concurrent requests
     */
    protected function createBarrier(int $count): \Closure
    {
        $barrier = new \stdClass();
        $barrier->count = $count;
        $barrier->reached = 0;
        $barrier->cond = new \Swoole\Coroutine\Channel(1);
        
        return function () use ($barrier) {
            $barrier->reached++;
            
            if ($barrier->reached === $barrier->count) {
                // Release all waiting coroutines
                for ($i = 0; $i < $barrier->count; $i++) {
                    $barrier->cond->push(true);
                }
            } else {
                // Wait for the barrier to be reached
                $barrier->cond->pop(5); // 5 second timeout
            }
        };
    }
    
    /**
     * Execute a refund request asynchronously
     */
    protected function asyncRefundRequest(\Closure $barrier, float $amount, int $requestId = 0): array
    {
        $process = new \React\ChildProcess\Process(
            'php -r "' . $this->getRefundProcessCode($barrier, $amount, $requestId) . '"'
        );
        
        $process->start();
        
        return [
            'process' => $process,
            'requestId' => $requestId,
            'success' => null,
            'message' => '',
            'output' => '',
        ];
    }
    
    /**
     * Wait for all async requests to complete
     */
    protected function waitForResponses(array &$responses): void
    {
        // Wait for all processes to complete
        $this->waitForProcesses(array_column($responses, 'process'));
        
        // Collect outputs
        foreach ($responses as &$response) {
            $output = trim($response['process']->getOutput());
            $response['output'] = $output;
            
            // Parse the JSON output
            if ($json = json_decode($output, true)) {
                $response['success'] = $json['success'] ?? false;
                $response['message'] = $json['message'] ?? '';
            } else {
                $response['success'] = false;
                $response['message'] = 'Invalid response format';
            }
        }
    }
    
    /**
     * Wait for multiple processes to complete
     */
    protected function waitForProcesses(array $processes, float $timeout = 10.0): void
    {
        $start = microtime(true);
        
        while (true) {
            $allDone = true;
            
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $allDone = false;
                    break;
                }
            }
            
            if ($allDone) {
                break;
            }
            
            if ((microtime(true) - $start) > $timeout) {
                throw new \RuntimeException('Timeout waiting for processes to complete');
            }
            
            usleep(10000); // 10ms
        }
    }
    
    /**
     * Generate PHP code for the refund process
     */
    protected function getRefundProcessCode(\Closure $barrier, float $amount, int $requestId): string
    {
        $code = '\n'
            . 'require __DIR__."/vendor/autoload.php";\n'
            . '$app = require_once __DIR__."/bootstrap/app.php";\n'
            . '$kernel = $app->make(Illine\Contracts\Console\Kernel::class);\n'
            . '$kernel->bootstrap();\n'
            . 'use App\Models\Payment;\n'
            . 'use App\Services\RefundService;\n'
            . 'use Illuminate\Support\Facades\DB;\n'
            . 'try {\n'
            . '    $payment = Payment::first();\n'
            . '    $refundService = app(RefundService::class);\n'
            . '    $barrier = ' . $this->getClosureCode($barrier) . ';\n'
            . '    $barrier();\n'
            . '    $result = $refundService->initiateRefund(\n'
            . '        $payment,\n'
            . '        ' . $amount . ',\n'
            . '        "Test refund " . ' . $requestId . ',\n'
            . '        $payment->user,\n'
            . '        ["request_id" => ' . $requestId . ']\n'
            . '    );\n'
            . '    echo json_encode([\n'
            . '        "success" => $result["success"],\n'
            . '        "message" => $result["message"] ?? "",\n'
            . '        "request_id" => ' . $requestId . ',\n'
            . '    ]);\n'
            . '} catch (\\Exception $e) {\n'
            . '    echo json_encode([\n'
            . '        "success" => false,\n'
            . '        "message" => $e->getMessage(),\n'
            . '        "request_id" => ' . $requestId . ',\n'
            . '    ]);\n'
            . '}\n';

        return str_replace('\n', "\n", $code);
    }
    
    /**
     * Generate PHP code for a closure
     */
    protected function getClosureCode(\Closure $closure): string
    {
        $reflection = new \ReflectionFunction($closure);
        $filename = $reflection->getFileName();
        $startLine = $reflection->getStartLine();
        $endLine = $reflection->getEndLine();
        
        $code = file($filename);
        $code = implode("", array_slice($code, $startLine - 1, $endLine - $startLine + 1));
        
        return $code;
    }
    
    /**
     * Generate PHP code for a process
     */
    protected function getProcessCode(\Closure $closure): string
    {
        $code = '\n'
            . 'require __DIR__."/vendor/autoload.php";\n'
            . '$app = require_once __DIR__."/bootstrap/app.php";\n'
            . '$kernel = $app->make(Illine\Contracts\Console\Kernel::class);\n'
            . '$kernel->bootstrap();\n'
            . 'use App\Models\Refund;\n'
            . 'use Illuminate\Support\Facades\DB;\n'
            . 'try {\n'
            . '    ' . $this->getClosureCode($closure) . '\n'
            . '} catch (\\Exception $e) {\n'
            . '    echo json_encode(["success" => false, "message" => $e->getMessage()]);\n'
            . '}\n';

        return str_replace('\n', "\n", $code);
    }
}
