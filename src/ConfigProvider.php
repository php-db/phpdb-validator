<?php

declare(strict_types=1);

namespace PhpDb\Validator;

use Laminas\ServiceManager\Factory\InvokableFactory;

final class ConfigProvider
{
    /**
     * Return configuration for the validator plugin manager.
     *
     * @return array{factories: array<class-string, class-string>, aliases: array<string, class-string>}
     */
    public function getValidatorConfig(): array
    {
        return [
            'factories' => [
                RecordExists::class   => InvokableFactory::class,
                NoRecordExists::class => InvokableFactory::class,
            ],
            'aliases'   => [
                'dbnorecordexists' => NoRecordExists::class,
                'dbNoRecordExists' => NoRecordExists::class,
                'DbNoRecordExists' => NoRecordExists::class,
                'dbrecordexists'   => RecordExists::class,
                'dbRecordExists'   => RecordExists::class,
                'DbRecordExists'   => RecordExists::class,
            ],
        ];
    }

    /**
     * @return array{validators: array{factories: array<class-string, class-string>, aliases: array<string, class-string>}}
     */
    public function __invoke(): array
    {
        return [
            'validators' => $this->getValidatorConfig(),
        ];
    }
}
