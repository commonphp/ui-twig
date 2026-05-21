<?php

declare(strict_types=1);

namespace CommonPHP\Drivers\UI\Twig;

use CommonPHP\UI\Template;
use CommonPHP\UI\ViewData;

final class TwigTemplate extends Template
{
    /**
     * @param array<string, mixed>|ViewData $data
     */
    public static function named(string $name, array|ViewData $data = []): static
    {
        return new static($name, $data);
    }

    /**
     * @param array<string, mixed>|ViewData $data
     */
    public static function file(string $path, array|ViewData $data = [], ?string $name = null): static
    {
        return new static($name ?? $path, $data, $path);
    }
}
