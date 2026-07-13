<?php

declare(strict_types=1);

namespace PhpDb\Validator;

use Laminas\ServiceManager\Factory\InvokableFactory;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'validators' => $this->getValidatorConfig(),
        ];
    }

    /**
     * Return configuration for the validator plugin manager.
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
}
