<?php

declare(strict_types=1);

namespace Chronhub\Chronicler\Exception;

use Throwable;
use PDOException;
use Illuminate\Database\QueryException;
use function sprintf;

class QueryFailure extends RuntimeException
{
    public static function fromQueryException(QueryException $queryException): self
    {
        return new self(
            self::getPreviousExceptionMessage($queryException->getPrevious()),
            (int) $queryException->getCode(),
            $queryException
        );
    }

    protected static function getPreviousExceptionMessage(Throwable $previousException): string
    {
        if ($previousException instanceof PDOException) {
            $errorInfo = $previousException->errorInfo;

            return sprintf("Error %s. \nError-Info: %s", $errorInfo[0], $errorInfo[2]);
        }

        return $previousException->getMessage();
    }
}
