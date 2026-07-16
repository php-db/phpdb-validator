<?php

declare(strict_types=1);

namespace PhpDb\Validator;

use Closure;
use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\Exception\InvalidArgumentException;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql\Predicate\PredicateInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;
use PhpDb\Sql\Where;

use function is_array;
use function is_scalar;
use function is_string;

/**
 * Parsed constructor options for database record validators.
 *
 * @psalm-type OptionsArgument = array{
 * adapter?: Adapter,
 * select?: string|array|Select,
 * table?: string|TableIdentifier,
 * schema?: string,
 * field?: string,
 * exclude?: array{field: string, value: scalar}|Closure|PredicateInterface|Where|string,
 * messages?: array<string, string>,
 * translator?: TranslatorInterface|null,
 * translatorTextDomain?: string|null,
 * translatorEnabled?: bool,
 * valueObscured?: bool,
 * }
 * @psalm-type ParentOptionsArgument = array{
 * messages?: array<string, string>,
 * translator?: TranslatorInterface|null,
 * translatorTextDomain?: string|null,
 * translatorEnabled?: bool,
 * valueObscured?: bool,
 * }
 *
 * @api
 */
final readonly class Options
{
    public AdapterInterface $adapter;

    public TableIdentifier $table;

    public string $field;

    public ?Select $select;

    /** @var array{field: string, value: scalar}|Closure|PredicateInterface|Where|string|null */
    public array|Closure|PredicateInterface|Where|string|null $exclude;

    /** @var ParentOptionsArgument Remaining options passed through to the parent validator */
    public array $params;

    /**
     * @param OptionsArgument $options
     * @throws InvalidArgumentException
     */
    public function __construct(array $options)
    {
        $this->adapter = $this->getAdapterOption($options);
        $this->table   = $this->getTableOption($options);
        $this->field   = $this->getFieldOption($options);
        $this->exclude = $this->getExcludeOption($options);
        $this->select  = $this->getSelectOption($options);

        /** @var ParentOptionsArgument $options */
        $this->params = $options;
    }

    /**
     * @param OptionsArgument $options
     * @throws InvalidArgumentException
     */
    private function getAdapterOption(array &$options): AdapterInterface
    {
        $adapter = $options['adapter'] ?? null;
        unset($options['adapter']);

        if (! $adapter instanceof AdapterInterface) {
            throw new InvalidArgumentException('Adapter option missing.');
        }

        return $adapter;
    }

    /**
     * @param OptionsArgument $options
     * @return array{field: string, value: scalar}|Closure|PredicateInterface|Where|string|null
     * @throws InvalidArgumentException
     */
    private function getExcludeOption(array &$options): array|Closure|PredicateInterface|Where|string|null
    {
        $exclude = $options['exclude'] ?? null;
        unset($options['exclude']);

        return match (true) {
            ! is_array($exclude) => $exclude,
            is_string($exclude['field'] ?? null) && is_scalar($exclude['value'] ?? null) => [
                'field' => $exclude['field'],
                'value' => $exclude['value'],
            ],
            default => throw new InvalidArgumentException("Exclude array must contain 'field' and 'value' keys"),
        };
    }

    /**
     * @param OptionsArgument $options
     * @throws InvalidArgumentException
     */
    private function getFieldOption(array &$options): string
    {
        $field = $options['field'] ?? '';
        unset($options['field']);

        if ('' === $field) {
            throw new InvalidArgumentException('Field option missing.');
        }

        return $field;
    }

    /**
     * @param OptionsArgument $options
     */
    private function getSelectOption(array &$options): ?Select
    {
        $select = $options['select'] ?? null;
        unset($options['select']);

        return $select instanceof Select ? $select : null;
    }

    /**
     * @param OptionsArgument $options
     * @throws InvalidArgumentException
     */
    private function getTableOption(array &$options): TableIdentifier
    {
        $table  = $options['table'] ?? null;
        $schema = $options['schema'] ?? null;
        unset($options['table'], $options['schema']);

        return match (true) {
            $table instanceof TableIdentifier && null !== $schema => throw new InvalidArgumentException(
                'Schema option must not be combined with a TableIdentifier table option.',
            ),
            $table instanceof TableIdentifier => $table,
            is_string($table) && '' !== $table => new TableIdentifier($table, $schema),
            default => throw new InvalidArgumentException('Table option missing.'),
        };
    }
}
