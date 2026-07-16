<?php

declare(strict_types=1);

namespace PhpDbTestAsset\Validator;

use Override;
use PhpDb\Validator\AbstractDbValidator;
use PhpDb\Validator\Options;

/**
 * @psalm-import-type OptionsArgument from Options
 */
final class ConcreteDbValidator extends AbstractDbValidator
{
    public const FOO_MESSAGE = 'fooMessage';
    public const BAR_MESSAGE = 'barMessage';

    /** @var array<string, string> */
    public array $messageTemplates = [
        'fooMessage' => '%value% was passed',
        'barMessage' => '%value% was wrong',
    ];

    #[Override]
    public function isValid(mixed $value): bool
    {
        $this->setValue($value);
        $this->error(self::FOO_MESSAGE);
        $this->error(self::BAR_MESSAGE);
        return false;
    }
}
