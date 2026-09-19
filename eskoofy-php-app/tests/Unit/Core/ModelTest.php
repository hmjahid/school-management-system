<?php
declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Model;
use Tests\TestCase;

class TestModel extends Model
{
    protected static string $table = 'test_models';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = ['name', 'value'];
    protected array $hidden = ['secret'];
    protected array $casts = [
        'value' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'json',
    ];
}

class SoftDeleteModel extends Model
{
    protected static string $table = 'soft_models';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = ['name'];
}

class ModelTest extends TestCase
{
    public function test_construct_with_attributes(): void
    {
        $model = new TestModel(['name' => 'test', 'value' => 42]);
        $this->assertSame('test', $model->name);
        $this->assertSame(42, $model->value);
    }

    public function test_magic_get(): void
    {
        $model = new TestModel(['name' => 'hello']);
        $this->assertSame('hello', $model->name);
        $this->assertNull($model->nonexistent);
    }

    public function test_magic_set(): void
    {
        $model = new TestModel();
        $model->name = 'world';
        $this->assertSame('world', $model->name);
    }

    public function test_magic_isset(): void
    {
        $model = new TestModel(['name' => 'test']);
        $this->assertTrue(isset($model->name));
        $this->assertFalse(isset($model->missing));
    }

    public function test_to_array_applies_hidden(): void
    {
        $model = new TestModel(['name' => 'test', 'secret' => 'hidden']);
        $data = $model->toArray();
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayNotHasKey('secret', $data);
    }

    public function test_to_array_applies_casts(): void
    {
        $model = new TestModel(['value' => '42', 'is_active' => '1', 'metadata' => '{"key":"val"}']);
        $data = $model->toArray();
        $this->assertIsInt($data['value']);
        $this->assertSame(42, $data['value']);
        $this->assertIsBool($data['is_active']);
        $this->assertTrue($data['is_active']);
        $this->assertIsArray($data['metadata']);
        $this->assertSame(['key' => 'val'], $data['metadata']);
    }

    public function test_to_json(): void
    {
        $model = new TestModel(['name' => 'test', 'value' => 1]);
        $json = $model->toJson();
        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertSame('test', $decoded['name']);
    }

    public function test_soft_deletes_flag(): void
    {
        $model = new TestModel();
        $ref = new \ReflectionClass($model);
        $prop = $ref->getProperty('softDeletes');
        $prop->setValue($model, false);
        $this->assertFalse($prop->getValue($model));

        $softModel = new SoftDeleteModel();
        $prop2 = new \ReflectionClass($softModel);
        $prop2Soft = $prop2->getProperty('softDeletes');
        $this->assertTrue($prop2Soft->getValue($softModel));
    }
}
