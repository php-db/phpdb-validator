<?php

declare(strict_types=1);

namespace PhpDbTest\Validator\Unit;

use ArrayObject;
use Laminas\Validator\Exception\InvalidArgumentException;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\ParameterContainer;
use PhpDb\Adapter\Platform\Sql92;
use PhpDb\Sql\Select;
use PhpDb\Sql\Sql;
use PhpDb\Sql\TableIdentifier;
use PhpDb\Validator\RecordExists;
use PhpDbTestAsset\Validator\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class RecordExistsTest extends TestCase
{
    /**
     * Test basic function of RecordExists (no exclusion)
     *
     * @throws Exception
     * @return void
     */
    #[Test]
    public function basicFindsNoRecord()
    {
        $validator = new RecordExists([
            'table'   => 'users',
            'field'   => 'field1',
            'adapter' => $this->getMockNoResult(),
        ]);
        static::assertFalse($validator->isValid('nosuchvalue'));
    }

    /**
     * Test basic function of RecordExists (no exclusion)
     *
     * @throws Exception
     * @return void
     */
    #[Test]
    public function basicFindsRecord()
    {
        $validator = new RecordExists([
            'table'   => 'users',
            'field'   => 'field1',
            'adapter' => $this->getMockHasResult(),
        ]);
        static::assertTrue($validator->isValid('value1'));
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function excludeConstructor(): void
    {
        $validator = new RecordExists([
            'table'   => 'users',
            'field'   => 'field1',
            'exclude' => 'id != 1',
            'adapter' => $this->getMockHasResult(),
        ]);
        static::assertTrue($validator->isValid('value3'));
    }

    /**
     * Test the exclusion function
     *
     * @throws Exception
     * @return void
     */
    #[Test]
    public function excludeWithArray()
    {
        $validator = new RecordExists([
            'table'   => 'users',
            'field'   => 'field1',
            'exclude' => [
                'field' => 'id',
                'value' => 1,
            ],
            'adapter' => $this->getMockHasResult(),
        ]);
        static::assertTrue($validator->isValid('value3'));
    }

    /**
     * Test the exclusion function
     * with an array
     *
     * @throws Exception
     * @return void
     */
    #[Test]
    public function excludeWithArrayNoRecord()
    {
        $validator = new RecordExists([
            'table'   => 'users',
            'field'   => 'field1',
            'exclude' => [
                'field' => 'id',
                'value' => 1,
            ],
            'adapter' => $this->getMockNoResult(),
        ]);
        static::assertFalse($validator->isValid('nosuchvalue'));
    }

    /**
     * Test the exclusion function
     * with a string
     *
     * @throws Exception
     * @return void
     */
    #[Test]
    public function excludeWithString()
    {
        $validator = new RecordExists([
            'table'   => 'users',
            'field'   => 'field1',
            'exclude' => 'id != 1',
            'adapter' => $this->getMockHasResult(),
        ]);
        static::assertTrue($validator->isValid('value3'));
    }

    /**
     * Test the exclusion function
     * with a string
     *
     * @throws Exception
     * @return void
     */
    #[Test]
    public function excludeWithStringNoRecord()
    {
        $validator = new RecordExists([
            'table'   => 'users',
            'field'   => 'field1',
            'exclude' => 'id != 1',
            'adapter' => $this->getMockNoResult(),
        ]);
        static::assertFalse($validator->isValid('nosuchvalue'));
    }

    /**
     * @throws Exception
     */
    #[Test]
    #[TestDox('PhpDb\Validator\RecordExists::getSelect')]
    public function getSelect(): void
    {
        $validator = new RecordExists([
            'table'   => 'users',
            'schema'  => 'my',
            'field'   => 'field1',
            'exclude' => [
                'field' => 'foo',
                'value' => 'bar',
            ],
            'adapter' => $this->getMockHasResult(),
        ]);
        $select = $validator->getSelect();
        static::assertInstanceOf(Select::class, $select);
        static::assertSame(
            'SELECT "my"."users"."field1" AS "field1" FROM "my"."users" WHERE "field1" = \'\' AND "foo" != \'bar\'',
            $select->getSqlString(new TrustingSql92Platform()),
        );

        $sql        = new Sql($this->getMockHasResult());
        $statement  = $sql->prepareStatementForSqlObject($select);
        $parameters = $statement->getParameterContainer();
        static::assertNotNUll($parameters);

        static::assertSame('', $parameters['where1']);
        static::assertSame('bar', $parameters['where2']);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getSelectWithSameValidatorTwice(): void
    {
        $validator = new RecordExists([
            'table'   => 'users',
            'schema'  => 'my',
            'field'   => 'field1',
            'exclude' => [
                'field' => 'foo',
                'value' => 'bar',
            ],
            'adapter' => $this->getMockHasResult(),
        ]);
        $select = $validator->getSelect();
        static::assertInstanceOf(Select::class, $select);
        static::assertSame(
            'SELECT "my"."users"."field1" AS "field1" FROM "my"."users" WHERE "field1" = \'\' AND "foo" != \'bar\'',
            $select->getSqlString(new TrustingSql92Platform()),
        );
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function returnsNoRecordFoundMessageWhenRecordDoesNotExist(): void
    {
        $validator = new RecordExists([
            'adapter' => $this->getMockNoResult(),
            'table'   => 'users',
            'field'   => 'field1',
        ]);

        static::assertFalse($validator->isValid('value'));
        static::assertSame(['noRecordFound' => 'No record matching the input was found'], $validator->getMessages());
    }

    /**
     * Test that the supplied table and schema are successfully passed to the select
     * statement
     *
     * @throws Exception
     */
    #[Test]
    public function selectAcknowledgesTableAndSchema(): void
    {
        $validator = new RecordExists([
            'table'   => 'users',
            'schema'  => 'my',
            'field'   => 'field1',
            'adapter' => $this->getMockHasResult(),
        ]);
        static::assertSame(
            'SELECT "my"."users"."field1" AS "field1" FROM "my"."users" WHERE "field1" = \'\'',
            $validator->getSelect()->getSqlString(new TrustingSql92Platform()),
        );
    }

    /**
     * Test that a TableIdentifier table option is successfully passed to the select
     * statement
     *
     * @throws Exception
     */
    #[Test]
    public function selectAcknowledgesTableIdentifier(): void
    {
        $validator = new RecordExists([
            'table'   => new TableIdentifier('users', 'my'),
            'field'   => 'field1',
            'adapter' => $this->getMockHasResult(),
        ]);
        static::assertSame(
            'SELECT "my"."users"."field1" AS "field1" FROM "my"."users" WHERE "field1" = \'\'',
            $validator->getSelect()->getSqlString(new TrustingSql92Platform()),
        );
    }

    /**
     * Test that the class throws an exception if no adapter is provided
     * and no default is set.
     *
     * @return void
     */
    #[Test]
    public function throwsExceptionWithNoAdapter()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Adapter option missing.');
        $validator = new RecordExists([
            'table'   => 'users',
            'field'   => 'field1',
            'exclude' => 'id != 1',
        ]);
        $validator->isValid('nosuchvalue');
    }

    /**
     * Test that schemas are supported and run without error
     *
     * @throws Exception
     * @return void
     */
    #[Test]
    public function withSchema()
    {
        $validator = new RecordExists([
            'table'   => 'users',
            'schema'  => 'my',
            'field'   => 'field1',
            'adapter' => $this->getMockHasResult(),
        ]);
        static::assertTrue($validator->isValid('value1'));
    }

    /**
     * Test that schemas are supported and run without error
     *
     * @throws Exception
     */
    #[Test]
    public function withSchemaNoResult(): void
    {
        $validator = new RecordExists([
            'table'   => 'users',
            'schema'  => 'my',
            'field'   => 'field1',
            'adapter' => $this->getMockNoResult(),
        ]);
        static::assertFalse($validator->isValid('value1'));
    }

    /**
     * Return a Mock object for a Db result with rows
     *
     * @throws Exception
     */
    protected function getMockHasResult(): Adapter
    {
        // mock the adapter, driver, and parts
        $mockConnection = $this->createMock(ConnectionInterface::class);

        // Mock has result
        $mockHasResultRow = new ArrayObject(['one' => 'one']);

        $mockHasResult = $this->createMock(ResultInterface::class);
        $mockHasResult->method('current')->willReturn($mockHasResultRow);

        $mockHasResultStatement = $this->createMock(StatementInterface::class);
        $mockHasResultStatement->method('execute')->willReturn($mockHasResult);

        $mockHasResultStatement->method('getParameterContainer')->willReturn(new ParameterContainer());

        $mockHasResultDriver = $this->createMock(DriverInterface::class);
        $mockHasResultDriver->method('createStatement')->willReturn($mockHasResultStatement);
        $mockHasResultDriver->method('getConnection')->willReturn($mockConnection);

        return new Adapter($mockHasResultDriver, new Sql92());
    }

    /**
     * Return a Mock object for a Db result without rows
     *
     * @throws Exception
     */
    protected function getMockNoResult(): Adapter
    {
        // mock the adapter, driver, and parts
        $mockConnection = $this->createMock(ConnectionInterface::class);

        $mockNoResult = $this->createMock(ResultInterface::class);
        $mockNoResult->method('current')->willReturn(null);

        $mockNoResultStatement = $this->createMock(StatementInterface::class);
        $mockNoResultStatement->method('execute')->willReturn($mockNoResult);

        $mockNoResultStatement->method('getParameterContainer')->willReturn(new ParameterContainer());

        $mockNoResultDriver = $this->createMock(DriverInterface::class);
        $mockNoResultDriver->method('createStatement')->willReturn($mockNoResultStatement);
        $mockNoResultDriver->method('getConnection')->willReturn($mockConnection);

        return new Adapter($mockNoResultDriver, new Sql92());
    }
}
