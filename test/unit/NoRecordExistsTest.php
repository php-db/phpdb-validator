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
use PhpDb\Validator\NoRecordExists;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class NoRecordExistsTest extends TestCase
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
        $validator = new NoRecordExists([
            'table'   => 'users',
            'field'   => 'field1',
            'adapter' => $this->getMockNoResult(),
        ]);
        static::assertTrue($validator->isValid('nosuchvalue'));
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
        $validator = new NoRecordExists([
            'table'   => 'users',
            'field'   => 'field1',
            'adapter' => $this->getMockHasResult(),
        ]);
        static::assertFalse($validator->isValid('value1'));
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
        $validator = new NoRecordExists([
            'table'   => 'users',
            'field'   => 'field1',
            'exclude' => [
                'field' => 'id',
                'value' => 1,
            ],
            'adapter' => $this->getMockHasResult(),
        ]);
        static::assertFalse($validator->isValid('value3'));
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
        $validator = new NoRecordExists([
            'table'   => 'users',
            'field'   => 'users',
            'exclude' => [
                'field' => 'id',
                'value' => 1,
            ],
            'adapter' => $this->getMockNoResult(),
        ]);
        static::assertTrue($validator->isValid('nosuchvalue'));
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
        $validator = new NoRecordExists([
            'table'   => 'users',
            'field'   => 'users',
            'exclude' => 'id != 1',
            'adapter' => $this->getMockHasResult(),
        ]);
        static::assertFalse($validator->isValid('value3'));
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
        $validator = new NoRecordExists([
            'table'   => 'users',
            'field'   => 'users',
            'exclude' => 'id != 1',
            'adapter' => $this->getMockNoResult(),
        ]);
        static::assertTrue($validator->isValid('nosuchvalue'));
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function returnsRecordFoundMessageWhenRecordExists(): void
    {
        $validator = new NoRecordExists([
            'adapter' => $this->getMockHasResult(),
            'table'   => 'users',
            'field'   => 'field1',
        ]);

        static::assertFalse($validator->isValid('value'));
        static::assertSame(['recordFound' => 'A record matching the input was found'], $validator->getMessages());
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
        $validator = new NoRecordExists([
            'table'   => 'users',
            'field'   => 'users',
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
        $validator = new NoRecordExists([
            'table'   => 'users',
            'schema'  => 'my',
            'field'   => 'users',
            'adapter' => $this->getMockHasResult(),
        ]);
        static::assertFalse($validator->isValid('value1'));
    }

    /**
     * Test that schemas are supported and run without error
     *
     * @throws Exception
     * @return void
     */
    #[Test]
    public function withSchemaNoResult()
    {
        $validator = new NoRecordExists([
            'table'   => 'users',
            'schema'  => 'my',
            'field'   => 'users',
            'adapter' => $this->getMockNoResult(),
        ]);
        static::assertTrue($validator->isValid('value1'));
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
