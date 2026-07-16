<?php

declare(strict_types=1);

namespace PhpDb\Validator;

use Closure;
use Laminas\Validator\AbstractValidator;
use Laminas\Validator\Exception\InvalidArgumentException;
use PhpDb\Adapter\AdapterAwareInterface;
use PhpDb\Adapter\AdapterAwareTrait;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql\Predicate\PredicateInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\Sql;
use PhpDb\Sql\TableIdentifier;
use PhpDb\Sql\Where;

use function is_array;
use function is_scalar;

/**
 * Class for Database record validation
 *
 * @psalm-import-type OptionsArgument from Options
 *
 * @api
 */
abstract class AbstractDbValidator extends AbstractValidator implements AdapterAwareInterface
{
    use AdapterAwareTrait;

    /**
     * Error constants
     */
    public const ERROR_NO_RECORD_FOUND = 'noRecordFound';
    public const ERROR_RECORD_FOUND    = 'recordFound';

    /** @var array<string, string> Message templates */
    protected array $messageTemplates = [
        self::ERROR_NO_RECORD_FOUND => 'No record matching the input was found',
        self::ERROR_RECORD_FOUND    => 'A record matching the input was found',
    ];

    /**
     * Select object to use. can be set, or will be auto-generated
     */
    protected ?Select $select = null;

    protected TableIdentifier $table;

    protected string $field;

    /** @var array{field: string, value: scalar}|Closure|PredicateInterface|Where|string|null */
    protected array|Closure|PredicateInterface|Where|string|null $exclude;

    /**
     * Provides basic configuration for use with Laminas\Validator\Db Validators
     * Setting $exclude allows a single record to be excluded from matching.
     *
     * The following option keys are supported:
     * 'table'   => The database table to validate against, as a string or TableIdentifier
     * 'schema'  => The schema name; only valid when 'table' is a string
     * 'field'   => The field to check for a match
     * 'exclude' => An optional where clause or field/value pair to exclude from the query
     * 'select' => An optional Select instance to use
     * 'adapter' => An optional database adapter to use
     *
     * @param OptionsArgument $options = []
     * @throws InvalidArgumentException
     */
    public function __construct(array $options)
    {
        $optionsData = new Options($options);

        $this->adapter = $optionsData->adapter;
        $this->table   = $optionsData->table;
        $this->field   = $optionsData->field;
        $this->exclude = $optionsData->exclude;
        $this->select  = $optionsData->select;

        parent::__construct($optionsData->params);
    }

    /**
     * Returns the set adapter
     */
    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    /**
     * Returns the set exclude clause
     *
     * @return array{field: string, value: scalar}|Closure|PredicateInterface|Where|string|null
     */
    public function getExclude(): array|Closure|PredicateInterface|Where|string|null
    {
        return $this->exclude;
    }

    /**
     * Returns the set field
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * Returns the set schema
     */
    public function getSchema(): ?string
    {
        return $this->table->getSchema();
    }

    /**
     * Gets the select object to be used by the validator.
     * If no select object was supplied to the constructor,
     * then it will auto-generate one from the given table,
     * schema, field, and adapter options.
     *
     * @return Select The Select object which will be used if present
     */
    public function getSelect(): Select
    {
        if ($this->select instanceof Select) {
            return $this->select;
        }

        // Build select object
        $select = new Select();
        $select->from($this->table)->columns([$this->field]);

        $where = new Where();
        $where->equalTo($this->field, '');
        $select->where($where);

        $exclude = $this->exclude;
        match (true) {
            null === $exclude => null,
            $exclude instanceof Closure => $select->where($exclude),
            is_array($exclude) => $where->notEqualTo($exclude['field'], (string) $exclude['value']),
            default            => $select->where($exclude),
        };

        return $select;
    }

    /**
     * Returns the set table
     */
    public function getTable(): string
    {
        return $this->table->getTable();
    }

    /**
     * Run query and returns matches, or null if no matches are found.
     *
     * @return mixed when matches are found.
     */
    protected function query(mixed $value): mixed
    {
        $sql       = new Sql($this->getAdapter());
        $statement = $sql->prepareStatementForSqlObject($this->getSelect());

        $parameters = $statement->getParameterContainer();
        if (null !== $parameters) {
            if (! is_scalar($value) && null !== $value) {
                throw new InvalidArgumentException('Value must be string, integer or null');
            }
            $parameters['where1'] = $value;
        }

        return $statement->execute()?->current();
    }
}
