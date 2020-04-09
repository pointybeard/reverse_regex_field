<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\ReverseRegexField\Exceptions;

final class CouldNotFindUniqueValueException extends \Exception
{
    public function __construct($pattern, $code = 0, \Exception $previous = null)
    {
        parent::__construct(
            "Unable to find unique value for pattern '{$pattern}'. Suggest increasing scope of possible values.",
            $code,
            $previous
        );
    }
}
