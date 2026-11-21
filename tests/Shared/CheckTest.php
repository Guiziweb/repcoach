<?php

declare(strict_types=1);

namespace App\Tests\Shared;

use App\Shared\Refused;
use App\Tests\Checker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class CheckTest extends TestCase
{
    public function testItAcceptsAValueThatRespectsTheConstraints(): void
    {
        Checker::make()('Pecs', [new NotBlank(), new Length(max: 10)]);

        $this->expectNotToPerformAssertions();
    }

    public function testItRefusesWithTheMessageOfTheViolation(): void
    {
        $this->expectException(Refused::class);
        $this->expectExceptionMessageIsOrContains('This value should not be blank.');

        Checker::make()('', new NotBlank());
    }

    public function testItRefusesOnTheFirstViolationOnly(): void
    {
        $this->expectException(Refused::class);
        $this->expectExceptionMessageIsOrContains('This value should not be blank.');

        Checker::make()('', [new NotBlank(), new Length(min: 3)]);
    }
}
