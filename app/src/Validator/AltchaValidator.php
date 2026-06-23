<?php

declare(strict_types=1);

namespace App\Validator;

use App\Service\AltchaService;
use Random\RandomException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class AltchaValidator extends ConstraintValidator
{
    public function __construct(
        private readonly AltchaService $altchaService,
    ) {
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param mixed $value The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     *
     * @throws RandomException
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Altcha) {
            throw new \LogicException('Unexpected constraint type.');
        }

        if (!is_string($value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();

            return;
        }

        $result = $this->altchaService->verifySolution($value);

        if (!$result) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
