<?php

declare(strict_types=1);

namespace PhpDb\Validator;

use Override;

/**
 * Confirms a record exists in a table.
 *
 * @mago-ignore analysis:missing-constructor
 */
final class RecordExists extends AbstractDbValidator
{
    #[Override]
    public function isValid(mixed $value): bool
    {
        $valid = true;
        $this->setValue($value);

        if (! $this->query($value)) {
            $valid = false;
            $this->error(self::ERROR_NO_RECORD_FOUND);
        }

        return $valid;
    }
}
