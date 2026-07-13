<?php

declare(strict_types=1);

namespace PhpDbTest\Validator\TestAsset;

use Override;
use PhpDb\Adapter\Platform\Sql92;

final class TrustingSql92Platform extends Sql92
{
    /**
     * {@inheritDoc}
     */
    #[Override]
    public function quoteValue(string $value): string
    {
        return (string) $this->quoteTrustedValue($value);
    }
}
