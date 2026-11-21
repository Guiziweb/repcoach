<?php

declare(strict_types=1);

namespace App\Shared;

use Attribute;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\RangeValidator;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class Minutes extends Range
{
    public function __construct()
    {
        parent::__construct(min: 1, max: Limits::MAX_MINUTES, notInRangeMessage: 'A workout lasts between {{ min }} and {{ max }} minutes.');
    }

    public function validatedBy(): string
    {
        return RangeValidator::class;
    }
}
