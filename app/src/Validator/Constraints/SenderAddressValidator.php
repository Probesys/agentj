<?php

namespace App\Validator\Constraints;

use App\Service\SenderAddressSanitizer;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class SenderAddressValidator extends ConstraintValidator
{
    public function __construct(
        private SenderAddressSanitizer $senderAddressSanitizer,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SenderAddress) {
            throw new UnexpectedTypeException($constraint, SenderAddress::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        if ($this->senderAddressSanitizer->sanitize($value) === null) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
