<?php

declare(strict_types=1);

namespace CommonPHP\Drivers\UI\Twig\Exceptions;

use CommonPHP\UI\Exceptions\RendererDriverException;
use Throwable;

class TwigRendererException extends RendererDriverException
{
    public static function forOperation(string $operation, ?Throwable $previous = null): self
    {
        $message = 'Twig renderer failed during ' . $operation;

        if ($previous !== null) {
            $message .= ': ' . $previous->getMessage();
        }

        return new self($message . '.', 0, $previous);
    }
}
