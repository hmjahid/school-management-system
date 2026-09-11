<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Router;
use Tests\TestCase;

class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();
        $this->router = new Router();
    }

    public function test_closure_route_get(): void
    {
        $called = false;
        $this->router->get('/test', function () use (&$called) {
            $called = true;
        });
        $this->router->dispatch('GET', '/test');
        $this->assertTrue($called);
    }

    public function test_route_param_name_must_match_controller_signature(): void
    {
        // Regression: /dashboard/seat-plans/{id}/generate crashed with
        // "Unknown named parameter $id" because the route declared {id}
        // while SeatPlanController::generate(int $examId) / 
        // ProgressReportController::generate(int $studentId) used a
        // different name. Router dispatches params by name, so the route
        // parameter must equal the action's parameter name.
        $received = null;
        $this->router->get('/progress-reports/{studentId}/generate', function (int $studentId) use (&$received) {
            $received = $studentId;
        });
        $this->router->dispatch('GET', '/progress-reports/7/generate');
        $this->assertSame(7, $received);
    }

    public function test_closure_route_post(): void
    {
        $called = false;
        $this->router->post('/submit', function () use (&$called) {
            $called = true;
        });
        $this->router->dispatch('POST', '/submit');
        $this->assertTrue($called);
    }

    public function test_closure_route_put(): void
    {
        $called = false;
        $this->router->put('/update', function () use (&$called) {
            $called = true;
        });
        $this->router->dispatch('PUT', '/update');
        $this->assertTrue($called);
    }

    public function test_closure_route_delete(): void
    {
        $called = false;
        $this->router->delete('/remove', function () use (&$called) {
            $called = true;
        });
        $this->router->dispatch('DELETE', '/remove');
        $this->assertTrue($called);
    }

    public function test_route_not_found_returns_404(): void
    {
        ob_start();
        $this->router->dispatch('GET', '/nonexistent');
        ob_end_clean();
        $this->assertSame(404, http_response_code());
    }

    public function test_method_mismatch(): void
    {
        $this->router->get('/only-get', function () {
            // should not be called
        });
        ob_start();
        $this->router->dispatch('POST', '/only-get');
        ob_end_clean();
        $this->assertSame(404, http_response_code());
    }

    public function test_group_prefix(): void
    {
        $called = false;
        $this->router->group('/api', function (Router $r) use (&$called) {
            $r->get('/users', function () use (&$called) {
                $called = true;
            });
        });
        $this->router->dispatch('GET', '/api/users');
        $this->assertTrue($called);
    }

    public function test_nested_groups(): void
    {
        $called = false;
        $this->router->group('/api', function (Router $r) use (&$called) {
            $r->group('/v1', function (Router $r2) use (&$called) {
                $r2->get('/items', function () use (&$called) {
                    $called = true;
                });
            });
        });
        $this->router->dispatch('GET', '/api/v1/items');
        $this->assertTrue($called);
    }

    public function test_trailing_slash_is_normalized(): void
    {
        $called = false;
        $this->router->get('/page', function () use (&$called) {
            $called = true;
        });
        $this->router->dispatch('GET', '/page/');
        $this->assertTrue($called);
    }

    public function test_route_with_params(): void
    {
        $params = null;
        $this->router->get('/users/{id}', function ($id) use (&$params) {
            $params = $id;
        });
        $this->router->dispatch('GET', '/users/42');
        $this->assertSame('42', $params);
    }

    public function test_first_match_wins(): void
    {
        $order = [];
        $this->router->get('/same', function () use (&$order) {
            $order[] = 'first';
        });
        $this->router->get('/same', function () use (&$order) {
            $order[] = 'second';
        });
        $this->router->dispatch('GET', '/same');
        $this->assertSame(['first'], $order);
    }

    public function test_query_string_stripped(): void
    {
        $called = false;
        $this->router->get('/page', function () use (&$called) {
            $called = true;
        });
        $this->router->dispatch('GET', '/page?foo=bar');
        $this->assertTrue($called);
    }
}
