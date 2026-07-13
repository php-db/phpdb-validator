<?php

declare(strict_types=1);

namespace PhpDb\Validator;

use Override;

/**
 * Confirms a record does not exist in a table.
 */
final class NoRecordExists extends AbstractDbValidator
{
    #[Override]
    public function isValid(mixed $value): bool
    {
        $valid = true;
        $this->setValue($value);

        if ($this->query($value) !== null) {
            $valid = false;
            $this->error(self::ERROR_RECORD_FOUND);
        }

        return $valid;
    }
}
