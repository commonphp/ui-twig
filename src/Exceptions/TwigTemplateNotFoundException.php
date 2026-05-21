<?php

declare(strict_types=1);

namespace CommonPHP\Drivers\UI\Twig\Exceptions;

use CommonPHP\UI\Exceptions\TemplateNotFoundException;

class TwigTemplateNotFoundException extends TemplateNotFoundException
{
    /**
     * @param list<string> $paths
     */
    public static function forTemplate(string $template, array $paths = []): self
    {
        $message = 'Twig template "' . $template . '" was not found.';

        if ($paths !== []) {
            $message .= ' Searched: ' . implode(', ', $paths) . '.';
        }

        return new self($message);
    }
}
