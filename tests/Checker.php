<?php

declare(strict_types=1);

namespace App\Tests;

use App\Shared\Check;
use Symfony\Component\Validator\Validation;

final class Checker
{
    public static function make(): Check
    {
        return new Check(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
    }
}
