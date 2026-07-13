<?php

declare(strict_types=1);

namespace PhpDb\Validator;

use Closure;
use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\AbstractValidator;
use Laminas\Validator\Exception;
use Laminas\Validator\Exception\InvalidArgumentException;
use PhpDb\Adapter\Adapter;
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
 * @psalm-type OptionsArgument = array{
 * adapter?: Adapter,
 * select?: string|array|Select,
 * table?: string,
 * schema?: string,
 * field?: string,
 * exclude?: array{field: string, value: scalar}|Closure|PredicateInterface|Where|string,
 * messages?: array<string, string>,
 * translator?: TranslatorInterface|null,
 * translatorTextDomain?: string|null,
 * translatorEnabled?: bool,
 * valueObscured?: bool,
 * }
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

    protected ?string $schema = null;

    protected string $table;

    protected string $field;

    /** @var array{field: string, value: scalar}|Closure|PredicateInterface|Where|string|null */
    protected array|Closure|PredicateInterface|Where|string|null $exclude;

    /**
     * Provides basic configuration for use with Laminas\Validator\Db Validators
     * Setting $exclude allows a single record to be excluded from matching.
     *
     * The following option keys are supported:
     * 'table'   => The database table to validate against
     * 'schema'  => The schema keys
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
        if (! isset($options['adapter'])) {
            throw new Exception\InvalidArgumentException('Adapter option missing.');
        }

        $this->adapter = $options['adapter'];
        unset($options['adapter']);

        $this->table = $options['table'] ?? '';
        unset($options['table']);

        $this->schema = $options['schema'] ?? null;
        unset($options['schema']);

        $this->field = $options['field'] ?? '';
        unset($options['field']);

        $this->exclude = $options['exclude'] ?? null;
        unset($options['exclude']);

        if (isset($options['select']) && $options['select'] instanceof Select) {
            $this->select = $options['select'];
            unset($options['select']);
        }

        if ($this->table === '' && $this->schema === null) {
            throw new Exception\InvalidArgumentException('Table or Schema option missing.');
        }

        if ($this->field === '') {
            throw new Exception\InvalidArgumentException('Field option missing.');
        }

        parent::__construct($options);
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
     * Returns the set table
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Returns the set schema
     */
    public function getSchema(): ?string
    {
        return $this->schema;
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
        $select          = new Select();
        $tableIdentifier = new TableIdentifier($this->table, $this->schema);
        $select->from($tableIdentifier)->columns([$this->field]);

        $where = new Where();
        $where->equalTo($this->field, '');
        $select->where($where);

        $exclude = $this->getExclude();
        if ($exclude !== null) {
            if (is_array($exclude)) {
                if (! isset($exclude['field'], $exclude['value'])) {
                    throw new Exception\InvalidArgumentException(
                        "Exclude array must contain 'field' and 'value' keys"
                    );
                }
                $where->notEqualTo(
                    $exclude['field'],
                    (string) $exclude['value']
                );
            } else {
                $select->where($exclude);
            }
        }

        return $select;
    }

    /**
     * Run query and returns matches, or null if no matches are found.
     *
     * @return mixed when matches are found.
     */
    protected function query(mixed $value): mixed
    {
        $sql       = new Sql($this->adapter);
        $statement = $sql->prepareStatementForSqlObject($this->getSelect());

        $parameters = $statement->getParameterContainer();
        if ($parameters !== null) {
            if (! is_scalar($value) && $value !== null) {
                throw new Exception\InvalidArgumentException('Value must be string, integer or null');
            }
            $parameters['where1'] = $value;
        }

        return $statement->execute()?->current();
    }
}
