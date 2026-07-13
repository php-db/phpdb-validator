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
use PhpDb\Validator\RecordExists;
use PhpDbTest\Validator\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class RecordExistsTest extends TestCase
{
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
        $mockHasResult
            ->method('current')
            ->willReturn($mockHasResultRow);

        $mockHasResultStatement = $this->createMock(StatementInterface::class);
        $mockHasResultStatement
            ->method('execute')
            ->willReturn($mockHasResult);

        $mockHasResultStatement
            ->method('getParameterContainer')
            ->willReturn(new ParameterContainer());

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
        $mockNoResult
            ->method('current')
            ->willReturn(null);

        $mockNoResultStatement = $this->createMock(StatementInterface::class);
        $mockNoResultStatement
            ->method('execute')
            ->willReturn($mockNoResult);

        $mockNoResultStatement
            ->method('getParameterContainer')
            ->willReturn(new ParameterContainer());

        $mockNoResultDriver = $this->createMock(DriverInterface::class);
        $mockNoResultDriver
            ->method('createStatement')
            ->willReturn($mockNoResultStatement);
        $mockNoResultDriver
            ->method('getConnection')
            ->willReturn($mockConnection);

        return new Adapter($mockNoResultDriver, new Sql92());
    }

    /**
     * Test basic function of RecordExists (no exclusion)
     *
     * @throws Exception
     * @return void
     */
    public function testBasicFindsRecord()
    {
        $validator = new RecordExists([
            'table'   => 'users',
            'field'   => 'field1',
            'adapter' => $this->getMockHasResult(),
        ]);
        static::assertTrue($validator->isValid('value1'));
    }

    /**
     * Test basic function of RecordExists (no exclusion)
     *
     * @throws Exception
     * @return void
     */
    public function testBasicFindsNoRecord()
    {
        $validator = new RecordExists([
            'table'   => 'users',
            'field'   => 'field1',
            'adapter' => $this->getMockNoResult(),
        ]);
        static::assertFalse($validator->isValid('nosuchvalue'));
    }

    /**
     * Test the exclusion function
     *
     * @throws Exception
     * @return void
     */
    public function testExcludeWithArray()
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
    public function testExcludeWithArrayNoRecord()
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
    public function testExcludeWithString()
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
    public function testExcludeWithStringNoRecord()
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
    public function testExcludeConstructor(): void
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
     * Test that the class throws an exception if no adapter is provided
     * and no default is set.
     *
     * @return void
     */
    public function testThrowsExceptionWithNoAdapter()
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
    public function testWithSchema()
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
    public function testWithSchemaNoResult(): void
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
     * Test that the supplied table and schema are successfully passed to the select
     * statement
     *
     * @throws Exception
     */
    public function testSelectAcknowledgesTableAndSchema(): void
    {
        $validator = new RecordExists([
            'table'   => 'users',
            'schema'  => 'my',
            'field'   => 'field1',
            'adapter' => $this->getMockHasResult(),
        ]);
        static::assertSame(
            'SELECT "my"."users"."field1" AS "field1" FROM "my"."users" WHERE "field1" = \'\'',
            $validator->getSelect()->getSqlString(new TrustingSql92Platform())
        );
    }

    /**
     * @throws Exception
     */
    public function testReturnsNoRecordFoundMessageWhenRecordDoesNotExist(): void
    {
        $validator = new RecordExists([
            'adapter' => $this->getMockNoResult(),
            'table'   => 'users',
            'field'   => 'field1',
        ]);

        static::assertFalse($validator->isValid('value'));
        static::assertSame(
            ['noRecordFound' => 'No record matching the input was found'],
            $validator->getMessages()
        );
    }

    /**
     * @testdox PhpDb\Validator\RecordExists::getSelect
     * @throws Exception
     */
    public function testGetSelect(): void
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
        $select    = $validator->getSelect();
        static::assertInstanceOf(Select::class, $select);
        static::assertSame(
            'SELECT "my"."users"."field1" AS "field1" FROM "my"."users" WHERE "field1" = \'\' AND "foo" != \'bar\'',
            $select->getSqlString(new TrustingSql92Platform())
        );

        $sql        = new Sql($this->getMockHasResult());
        $statement  = $sql->prepareStatementForSqlObject($select);
        $parameters = $statement->getParameterContainer();
        static::assertNotNUll($parameters);

        static::assertSame('', $parameters['where1']);
        static::assertSame('bar', $parameters['where2']);
    }

    /**
     * @cover PhpDb\Validator\RecordExists::getSelect
     * @throws Exception
     */
    public function testGetSelectWithSameValidatorTwice(): void
    {
        $validator = new RecordExists(
            [
                'table'   => 'users',
                'schema'  => 'my',
                'field'   => 'field1',
                'exclude' => [
                    'field' => 'foo',
                    'value' => 'bar',
                ],
                'adapter' => $this->getMockHasResult(),
            ]
        );
        $select    = $validator->getSelect();
        static::assertInstanceOf(Select::class, $select);
        static::assertSame(
            'SELECT "my"."users"."field1" AS "field1" FROM "my"."users" WHERE "field1" = \'\' AND "foo" != \'bar\'',
            $select->getSqlString(new TrustingSql92Platform())
        );
    }
}
