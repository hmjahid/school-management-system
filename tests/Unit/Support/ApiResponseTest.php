<?php

namespace Tests\Unit\Support;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class ApiResponseTest extends TestCase
{
    /** Fake controller that uses the ApiResponse trait so we can exercise it. */
    private function harness(): object
    {
        return new class
        {
            use ApiResponse;
        };
    }

    private function invokeTrait(object $harness, string $method, ...$args)
    {
        $ref = new ReflectionMethod($harness, $method);
        $ref->setAccessible(true);

        return $ref->invoke($harness, ...$args);
    }

    private function decode($response): array
    {
        return json_decode($response->getContent(), true);
    }

    #[Test]
    public function success_returns_envelope_with_true(): void
    {
        $response = $this->invokeTrait($this->harness(), 'success', ['foo' => 'bar'], 'All good', 200);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $this->decode($response);
        $this->assertTrue($data['success']);
        $this->assertEquals('All good', $data['message']);
        $this->assertEquals(['foo' => 'bar'], $data['data']);
    }

    #[Test]
    public function success_omits_meta_when_empty(): void
    {
        $data = $this->decode($this->invokeTrait($this->harness(), 'success', null, 'Okay'));

        $this->assertArrayNotHasKey('meta', $data);
    }

    #[Test]
    public function success_includes_meta_when_provided(): void
    {
        $data = $this->decode($this->invokeTrait($this->harness(), 'success', [], 'Okay', 200, ['page' => 2]));

        $this->assertEquals(['page' => 2], $data['meta']);
    }

    #[Test]
    public function created_uses_201_status(): void
    {
        $response = $this->invokeTrait($this->harness(), 'created', ['id' => 1]);

        $this->assertEquals(201, $response->getStatusCode());
        $data = $this->decode($response);
        $this->assertTrue($data['success']);
        $this->assertEquals('Created successfully', $data['message']);
    }

    #[Test]
    public function paginated_unwraps_items_and_adds_pagination_meta(): void
    {
        $items = collect([['id' => 1], ['id' => 2]]);
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator($items, 10, 2, 1);

        $data = $this->decode($this->invokeTrait($this->harness(), 'paginated', $paginator, 'List'));

        $this->assertTrue($data['success']);
        $this->assertEquals([['id' => 1], ['id' => 2]], $data['data']);
        $this->assertEquals(2, $data['meta']['pagination']['per_page']);
        $this->assertEquals(10, $data['meta']['pagination']['total']);
        $this->assertEquals(1, $data['meta']['pagination']['current_page']);
    }

    #[Test]
    public function error_returns_false_envelope(): void
    {
        $response = $this->invokeTrait($this->harness(), 'error', 'Bad request', 422, ['field' => ['required']], 'VALIDATION');

        $this->assertEquals(422, $response->getStatusCode());
        $data = $this->decode($response);
        $this->assertFalse($data['success']);
        $this->assertEquals('Bad request', $data['message']);
        $this->assertEquals('VALIDATION', $data['code']);
        $this->assertEquals(['field' => ['required']], $data['errors']);
    }

    #[Test]
    public function error_omits_optional_keys_when_null(): void
    {
        $data = $this->decode($this->invokeTrait($this->harness(), 'error', 'Nope'));

        $this->assertArrayNotHasKey('code', $data);
        $this->assertArrayNotHasKey('errors', $data);
    }

    #[Test]
    public function responses_include_request_id_when_present(): void
    {
        $this->app['request']->headers->set('X-Request-ID', 'req-123');

        $data = $this->decode($this->invokeTrait($this->harness(), 'success', []));

        $this->assertEquals('req-123', $data['request_id']);
    }

    #[Test]
    public function responses_omit_request_id_when_absent(): void
    {
        $data = $this->decode($this->invokeTrait($this->harness(), 'success', []));

        $this->assertArrayNotHasKey('request_id', $data);
    }
}
