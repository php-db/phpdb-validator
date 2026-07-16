<?php

declare(strict_types=1);

namespace PhpDbTest\Validator\Unit;

use Laminas\Validator\Exception\InvalidArgumentException;
use Override;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\Sql92;
use PhpDb\Sql\TableIdentifier;
use PhpDb\Validator\AbstractDbValidator;
use PhpDbTestAsset\Validator\ConcreteDbValidator;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class AbstractDbTest extends TestCase
{
    protected Adapter $adapter;
    protected AbstractDbValidator $validator;

    #[Test]
    public function acceptsTableIdentifierAsTableOption(): void
    {
        $validator = new ConcreteDbValidator([
            'adapter' => $this->adapter,
            'table'   => new TableIdentifier('users', 'my'),
            'field'   => 'field',
        ]);

        static::assertSame('users', $validator->getTable());
        static::assertSame('my', $validator->getSchema());
    }

    #[Test]
    public function constructorWithNoFieldKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field option missing.');
        new ConcreteDbValidator([
            'adapter' => $this->adapter,
            'schema'  => 'schema',
            'table'   => 'table',
        ]);
    }

    #[Test]
    public function getExclude(): void
    {
        $exclude         = 'foo = "bar"';
        $this->validator = new ConcreteDbValidator([
            'adapter' => $this->adapter,
            'table'   => 'test_table',
            'field'   => 'field',
            'exclude' => $exclude,
        ]);

        static::assertEquals($exclude, $this->validator->getExclude());

        $exclude         = ['field' => 'foo', 'value' => 'bar'];
        $this->validator = new ConcreteDbValidator([
            'adapter' => $this->adapter,
            'table'   => 'test_table',
            'field'   => 'field',
            'exclude' => $exclude,
        ]);

        static::assertEquals($exclude, $this->validator->getExclude());
    }

    #[Test]
    public function getField(): void
    {
        $field           = 'test_field';
        $this->validator = new ConcreteDbValidator([
            'adapter' => $this->adapter,
            'table'   => 'test_table',
            'field'   => $field,
        ]);

        static::assertEquals($field, $this->validator->getField());
    }

    #[Test]
    public function getSchema(): void
    {
        $schema          = 'test_db';
        $this->validator = new ConcreteDbValidator([
            'adapter' => $this->adapter,
            'field'   => 'field',
            'table'   => 'test_table',
            'schema'  => $schema,
        ]);

        static::assertEquals($schema, $this->validator->getSchema());
    }

    #[Test]
    public function getTable(): void
    {
        $table           = 'test_table';
        $this->validator = new ConcreteDbValidator([
            'adapter' => $this->adapter,
            'field'   => 'field',
            'table'   => $table,
        ]);

        static::assertEquals($table, $this->validator->getTable());
    }

    #[Test]
    public function throwsWhenSchemaIsCombinedWithTableIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Schema option must not be combined with a TableIdentifier table option.');
        new ConcreteDbValidator([
            'adapter' => $this->adapter,
            'table'   => new TableIdentifier('users', 'my'),
            'schema'  => 'my',
            'field'   => 'field',
        ]);
    }

    #[Test]
    public function throwsWhenTableOptionIsAnEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table option missing.');
        new ConcreteDbValidator([
            'adapter' => $this->adapter,
            'table'   => '',
            'field'   => 'field',
        ]);
    }

    #[Test]
    public function throwsWhenTableOptionIsMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table option missing.');
        new ConcreteDbValidator([
            'adapter' => $this->adapter,
            'field'   => 'field',
        ]);
    }

    #[Override]
    protected function setUp(): void
    {
        $mockConnection = $this->createMock(ConnectionInterface::class);

        $mockResult = $this->createMock(ResultInterface::class);
        $mockResult->method('current')->willReturn(null);

        $mockStatement = $this->createMock(StatementInterface::class);
        $mockStatement->method('execute')->willReturn($mockResult);

        $mockStatement->method('getParameterContainer')->willReturn(new ParameterContainer());

        $mockDriver = $this->createMock(DriverInterface::class);
        $mockDriver->method('createStatement')->willReturn($mockStatement);
        $mockDriver->method('getConnection')->willReturn($mockConnection);

        $this->adapter = new Adapter($mockDriver, new Sql92());

        $this->validator = new ConcreteDbValidator([
            'adapter' => $this->adapter,
            'table'   => 'table',
            'field'   => 'field',
            'schema'  => 'schema',
        ]);
    }
}
