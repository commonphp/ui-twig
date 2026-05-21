<?php

declare(strict_types=1);

namespace CommonPHP\Drivers\UI\Twig\Exceptions;

use CommonPHP\UI\Exceptions\RenderException;
use Throwable;

class TwigRenderException extends RenderException
{
    public static function forTemplate(string $template, Throwable $previous): self
    {
        return new self(
            'Unable to render Twig template "' . $template . '": ' . $previous->getMessage(),
            0,
            $previous,
        );
    }
}
