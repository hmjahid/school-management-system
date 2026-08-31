<?php

namespace Tests\Unit\Services;

use App\Services\Sms\TwilioSmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwilioSmsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function getProtected(object $obj, string $prop)
    {
        $r = new \ReflectionProperty($obj, $prop);
        $r->setAccessible(true);

        return $r->getValue($obj);
    }

    protected function callProtected(object $obj, string $method, ...$args)
    {
        $r = new \ReflectionMethod($obj, $method);
        $r->setAccessible(true);

        return $r->invokeArgs($obj, $args);
    }

    protected function makeService(array $config = []): TwilioSmsService
    {
        return new TwilioSmsService(array_merge([
            'account_sid' => 'ACtest',
            'auth_token' => 'secret',
        ], $config));
    }

    /** @test */
    public function it_sends_successfully_and_returns_true(): void
    {
        $service = $this->makeService();

        $this->assertTrue($service->send('01700000000', 'Hello'));
    }

    /** @test */
    public function it_formats_phone_number_into_e164(): void
    {
        $service = $this->makeService();

        $formatted = $this->callProtected($service, 'formatPhoneNumber', '0123456789');

        $this->assertSame('+1123456789', $formatted);
    }

    /** @test */
    public function it_detects_success_from_a_message_with_sid(): void
    {
        $service = $this->makeService();

        $this->assertTrue($this->callProtected($service, 'wasSuccessful', (object) ['sid' => 'SM123']));
        $this->assertFalse($this->callProtected($service, 'wasSuccessful', new \stdClass));
        $this->assertFalse($this->callProtected($service, 'wasSuccessful', null));
    }

    /** @test */
    public function it_returns_the_balance(): void
    {
        $service = $this->makeService();

        $this->assertSame(12.5, $service->getBalance());
    }

    /** @test */
    public function it_returns_the_status_of_a_message(): void
    {
        $service = $this->makeService();

        $status = $service->getStatus('SM123');

        $this->assertSame('delivered', $status['status']);
    }
}

namespace Twilio\Rest;

class Client
{
    public $messages;

    public $balance;

    public function __construct($sid = '', $token = '')
    {
        $this->messages = new \Tests\Unit\Services\TwilioMessagesResource;
        $this->balance = new \Tests\Unit\Services\TwilioBalanceResource;
    }

    public function messages($id = '')
    {
        return new \Tests\Unit\Services\TwilioMessageInstance($id);
    }
}

namespace Twilio\Exceptions;

class TwilioException extends \Exception {}

namespace Tests\Unit\Services;

class TwilioMessagesResource
{
    public function create($to, array $params = [])
    {
        return (object) ['sid' => 'SM123'];
    }
}

class TwilioBalanceResource
{
    public function fetch()
    {
        return (object) ['balance' => 12.5];
    }
}

class TwilioMessageInstance
{
    public $sid = 'SM123';

    public $status = 'delivered';

    public $dateCreated;

    public $dateUpdated;

    public $dateSent;

    public $errorCode = null;

    public $errorMessage = null;

    public $price = '0.00';

    public $priceUnit = 'USD';

    public $numSegments = 1;

    public $numMedia = 0;

    public $direction = 'outbound-api';

    public $apiVersion = '2010-04-01';

    public $uri = '/x';

    public function __construct($id = '')
    {
        $this->sid = $id ?: 'SM123';
        $now = new \Carbon\Carbon;
        $this->dateCreated = $now;
        $this->dateUpdated = $now;
        $this->dateSent = $now;
    }

    public function fetch()
    {
        return $this;
    }
}
