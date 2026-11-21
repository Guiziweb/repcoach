<?php

declare(strict_types=1);

namespace App\Shared;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class Check
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * @param Constraint|list<Constraint>|null $constraints
     */
    public function __invoke(mixed $value, Constraint|array|null $constraints = null): void
    {
        $violations = $this->validator->validate($value, $constraints);

        if (\count($violations) > 0) {
            throw new Refused((string) $violations->get(0)->getMessage());
        }
    }
}
