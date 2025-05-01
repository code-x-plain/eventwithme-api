<?php

namespace App\Api\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationException extends ApiException
{
    public function __construct(
        ConstraintViolationListInterface $violations, 
        string $message = 'Validation failed', 
        ?\Throwable $previous = null
    ) {
        $errors = [];
        
        foreach ($violations as $violation) {
            $propertyPath = $violation->getPropertyPath();
            $errors[$propertyPath] = $violation->getMessage();
        }
        
        parent::__construct($message, $errors, Response::HTTP_UNPROCESSABLE_ENTITY, $previous);
    }
} 