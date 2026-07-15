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
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class AbstractDbTest extends TestCase
{
    protected Adapter $adapter;
    protected AbstractDbValidator $validator;

    #[Override]
    protected function setUp(): void
    {
        $mockConnection = $this->createMock(ConnectionInterface::class);

        $mockResult = $this->createMock(ResultInterface::class);
        $mockResult
            ->method('current')
            ->willReturn(null);

        $mockStatement = $this->createMock(StatementInterface::class);
        $mockStatement
            ->method('execute')
            ->willReturn($mockResult);

        $mockStatement
            ->method('getParameterContainer')
            ->willReturn(new ParameterContainer());

        $mockDriver = $this->createMock(DriverInterface::class);
        $mockDriver
            ->method('createStatement')
            ->willReturn($mockStatement);
        $mockDriver
            ->method('getConnection')
            ->willReturn($mockConnection);

        $this->adapter = new Adapter($mockDriver, new Sql92());

        $this->validator = new ConcreteDbValidator([
            'adapter' => $this->adapter,
            'table'   => 'table',
            'field'   => 'field',
            'schema'  => 'schema',
        ]);
    }

    public function testThrowsWhenTableOptionIsMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table option missing.');
        new ConcreteDbValidator([
            'adapter' => $this->adapter,
            'field'   => 'field',
        ]);
    }

    public function testThrowsWhenTableOptionIsAnEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table option missing.');
        new ConcreteDbValidator([
            'adapter' => $this->adapter,
            'table'   => '',
            'field'   => 'field',
        ]);
    }

    public function testThrowsWhenSchemaIsCombinedWithTableIdentifier(): void
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

    public function testAcceptsTableIdentifierAsTableOption(): void
    {
        $validator = new ConcreteDbValidator([
            'adapter' => $this->adapter,
            'table'   => new TableIdentifier('users', 'my'),
            'field'   => 'field',
        ]);

        static::assertSame('users', $validator->getTable());
        static::assertSame('my', $validator->getSchema());
    }

    public function testConstructorWithNoFieldKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field option missing.');
        new ConcreteDbValidator([
            'adapter' => $this->adapter,
            'schema'  => 'schema',
            'table'   => 'table',
        ]);
    }

    public function testGetSchema(): void
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

    public function testGetTable(): void
    {
        $table           = 'test_table';
        $this->validator = new ConcreteDbValidator([
            'adapter' => $this->adapter,
            'field'   => 'field',
            'table'   => $table,
        ]);

        static::assertEquals($table, $this->validator->getTable());
    }

    public function testGetField(): void
    {
        $field           = 'test_field';
        $this->validator = new ConcreteDbValidator([
            'adapter' => $this->adapter,
            'table'   => 'test_table',
            'field'   => $field,
        ]);

        static::assertEquals($field, $this->validator->getField());
    }

    public function testGetExclude(): void
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
}
