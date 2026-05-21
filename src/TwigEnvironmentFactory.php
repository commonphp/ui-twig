<?php

declare(strict_types=1);

namespace CommonPHP\Drivers\UI\Twig;

use Twig\Environment;

final class TwigEnvironmentFactory
{
    public function create(?TwigTemplateLoader $loader = null, ?TwigRendererOptions $options = null): Environment
    {
        $options ??= new TwigRendererOptions();
        $loader ??= new TwigTemplateLoader($options->templatePaths());

        return new Environment($loader, $options->environmentOptions());
    }

    /**
     * @param array<string|int, mixed> $options
     */
    public function fromArray(array $options): Environment
    {
        $rendererOptions = TwigRendererOptions::fromArray($options);

        return $this->create(new TwigTemplateLoader($rendererOptions->templatePaths()), $rendererOptions);
    }
}
