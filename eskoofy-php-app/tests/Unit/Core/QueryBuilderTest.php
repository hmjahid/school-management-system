<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\QueryBuilder;
use Tests\TestCase;

class QueryBuilderTest extends TestCase
{
    public function test_select_builds_correct_sql(): void
    {
        $qb = new QueryBuilder('users');
        $qb->select('name', 'email');
        $ref = new \ReflectionClass($qb);
        $prop = $ref->getProperty('selectColumns');
        $prop->setValue($qb, ['name', 'email']);
        $this->assertSame(['name', 'email'], $prop->getValue($qb));
    }

    public function test_where_adds_condition(): void
    {
        $qb = new QueryBuilder('users');
        $qb->where('status', 'active');

        $ref = new \ReflectionClass($qb);
        $whereProp = $ref->getProperty('where');
        $paramsProp = $ref->getProperty('whereParams');

        $this->assertStringContainsString('status = ?', $whereProp->getValue($qb));
        $this->assertSame(['active'], $paramsProp->getValue($qb));
    }

    public function test_where_with_operator(): void
    {
        $qb = new QueryBuilder('users');
        $qb->where('age', 18, '>=');

        $ref = new \ReflectionClass($qb);
        $whereProp = $ref->getProperty('where');
        $this->assertStringContainsString('age >= ?', $whereProp->getValue($qb));
    }

    public function test_or_where(): void
    {
        $qb = new QueryBuilder('users');
        $qb->where('status', 'active')->orWhere('role', 'admin');

        $ref = new \ReflectionClass($qb);
        $whereProp = $ref->getProperty('where');
        $this->assertStringContainsString('OR', $whereProp->getValue($qb));
    }

    public function test_where_in(): void
    {
        $qb = new QueryBuilder('users');
        $qb->whereIn('id', [1, 2, 3]);

        $ref = new \ReflectionClass($qb);
        $whereProp = $ref->getProperty('where');
        $paramsProp = $ref->getProperty('whereParams');

        $this->assertStringContainsString('IN (?,?,?)', $whereProp->getValue($qb));
        $this->assertSame([1, 2, 3], $paramsProp->getValue($qb));
    }

    public function test_where_null(): void
    {
        $qb = new QueryBuilder('users');
        $qb->whereNull('deleted_at');

        $ref = new \ReflectionClass($qb);
        $whereProp = $ref->getProperty('where');
        $this->assertStringContainsString('deleted_at IS NULL', $whereProp->getValue($qb));
    }

    public function test_where_not_null(): void
    {
        $qb = new QueryBuilder('users');
        $qb->whereNotNull('email');

        $ref = new \ReflectionClass($qb);
        $whereProp = $ref->getProperty('where');
        $this->assertStringContainsString('email IS NOT NULL', $whereProp->getValue($qb));
    }

    public function test_where_date(): void
    {
        $qb = new QueryBuilder('users');
        $qb->whereDate('created_at', '2024-01-01');

        $ref = new \ReflectionClass($qb);
        $whereProp = $ref->getProperty('where');
        $this->assertStringContainsString('DATE(created_at) = ?', $whereProp->getValue($qb));
    }

    public function test_between(): void
    {
        $qb = new QueryBuilder('users');
        $qb->between('age', 18, 65);

        $ref = new \ReflectionClass($qb);
        $whereProp = $ref->getProperty('where');
        $paramsProp = $ref->getProperty('whereParams');

        $this->assertStringContainsString('BETWEEN ? AND ?', $whereProp->getValue($qb));
        $this->assertSame([18, 65], $paramsProp->getValue($qb));
    }

    public function test_join(): void
    {
        $qb = new QueryBuilder('users');
        $qb->join('orders', 'users.id', 'orders.user_id');

        $ref = new \ReflectionClass($qb);
        $joinsProp = $ref->getProperty('joins');

        $joins = $joinsProp->getValue($qb);
        $this->assertCount(1, $joins);
        $this->assertStringContainsString('INNER JOIN', $joins[0]);
    }

    public function test_left_join(): void
    {
        $qb = new QueryBuilder('users');
        $qb->leftJoin('orders', 'users.id', 'orders.user_id');

        $ref = new \ReflectionClass($qb);
        $joinsProp = $ref->getProperty('joins');
        $joins = $joinsProp->getValue($qb);
        $this->assertStringContainsString('LEFT JOIN', $joins[0]);
    }

    public function test_order_by(): void
    {
        $qb = new QueryBuilder('users');
        $qb->orderBy('name', 'DESC');

        $ref = new \ReflectionClass($qb);
        $orderByProp = $ref->getProperty('orderBy');
        $this->assertSame('ORDER BY name DESC', $orderByProp->getValue($qb));
    }

    public function test_limit_offset(): void
    {
        $qb = new QueryBuilder('users');
        $qb->limit(10)->offset(20);

        $ref = new \ReflectionClass($qb);
        $limitProp = $ref->getProperty('limit');
        $offsetProp = $ref->getProperty('offset');

        $this->assertSame(10, $limitProp->getValue($qb));
        $this->assertSame(20, $offsetProp->getValue($qb));
    }

    public function test_group_by_and_having(): void
    {
        $qb = new QueryBuilder('users');
        $qb->groupBy('role')->having('COUNT(*) > ?', [5]);

        $ref = new \ReflectionClass($qb);
        $groupByProp = $ref->getProperty('groupBy');
        $havingProp = $ref->getProperty('having');
        $havingParamsProp = $ref->getProperty('havingParams');

        $this->assertSame(['role'], $groupByProp->getValue($qb));
        $this->assertSame('COUNT(*) > ?', $havingProp->getValue($qb));
        $this->assertSame([5], $havingParamsProp->getValue($qb));
    }

    public function test_fluent_chaining(): void
    {
        $qb = new QueryBuilder('users');
        $result = $qb->select('name')->where('active', true)->orderBy('name')->limit(10);

        $this->assertSame($qb, $result);
    }

    public function test_where_raw(): void
    {
        $qb = new QueryBuilder('users');
        $qb->whereRaw('age > ? OR name LIKE ?', [18, '%admin%']);

        $ref = new \ReflectionClass($qb);
        $whereProp = $ref->getProperty('where');
        $paramsProp = $ref->getProperty('whereParams');

        $this->assertStringContainsString('(age > ? OR name LIKE ?)', $whereProp->getValue($qb));
        $this->assertSame([18, '%admin%'], $paramsProp->getValue($qb));
    }
}
