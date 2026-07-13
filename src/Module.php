<?php

declare(strict_types=1);

namespace PhpDb\Validator;

final class Module
{
    /**
     * Return default phpdb-validator configuration.
     *
     * @return array[]
     */
    public function getConfig(): array
    {
        $provider = new ConfigProvider();

        return [
            'validators' => $provider->getValidatorConfig(),
        ];
    }
}
