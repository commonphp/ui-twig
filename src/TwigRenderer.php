<?php

declare(strict_types=1);

namespace CommonPHP\Drivers\UI\Twig;

use CommonPHP\Drivers\UI\Twig\Exceptions\TwigRenderException;
use CommonPHP\Drivers\UI\Twig\Exceptions\TwigConfigurationException;
use CommonPHP\UI\ComponentRegistry;
use CommonPHP\UI\Contracts\AbstractRenderer;
use CommonPHP\UI\Contracts\ComponentInterface;
use CommonPHP\UI\Contracts\ComponentRegistryInterface;
use CommonPHP\UI\Contracts\TemplateInterface;
use CommonPHP\UI\Exceptions\UIException;
use CommonPHP\UI\View;
use CommonPHP\UI\ViewData;
use LogicException;
use Stringable;
use Throwable;
use Twig\Environment;
use Twig\TwigFunction;

final class TwigRenderer extends AbstractRenderer
{
    private TwigTemplateLoader $templates;

    private TwigComponentLoader $componentLoader;

    private Environment $environment;

    private TwigRendererOptions $options;

    /**
     * @param iterable<string|Stringable>|TwigRendererOptions|Environment|null $templatePaths
     */
    public function __construct(
        iterable|TwigRendererOptions|Environment|null $templatePaths = [],
        ?ComponentRegistryInterface $components = null,
        ?TwigRendererOptions $options = null,
        ?TwigTemplateLoader $loader = null,
        ?Environment $environment = null,
        ?TwigEnvironmentFactory $factory = null,
        ?TwigComponentLoader $componentLoader = null,
    ) {
        if ($templatePaths instanceof Environment) {
            $environment = $templatePaths;
            $templatePaths = [];
        }

        if ($templatePaths instanceof TwigRendererOptions) {
            $options = $templatePaths;
            $templatePaths = [];
        }

        if (is_array($templatePaths) && $this->isOptionsArray($templatePaths)) {
            $options = TwigRendererOptions::fromArray($templatePaths);
            $templatePaths = [];
        }

        $this->options = $options ?? new TwigRendererOptions($templatePaths ?? []);
        $this->templates = $loader ?? new TwigTemplateLoader($this->options->templatePaths());

        if (is_iterable($templatePaths)) {
            foreach ($templatePaths as $path) {
                $this->templates->addPath($path);
            }
        }

        $this->componentLoader = $componentLoader ?? new TwigComponentLoader($components ?? new ComponentRegistry());
        $this->environment = $environment ?? ($factory ?? new TwigEnvironmentFactory())->create(
            $this->templates,
            $this->options,
        );

        $this->registerComponentFunction();
    }

    public function render(View $view): string
    {
        $body = $this->renderTemplate(
            $view->template(),
            $this->mergedData($view->template()->data(), $view->data()),
        );

        $layout = $view->layout();

        if ($layout === null) {
            return $body;
        }

        $layoutData = $this->mergedData($view->data(), $layout->data())
            ->set($layout->contentKey(), $body);

        return $this->renderTemplate($layout, $layoutData);
    }

    public function renderTemplate(TemplateInterface|string $template, array|ViewData $data = []): string
    {
        $template = $this->template($template);
        $payload = $this->mergedData($template->data(), $data);
        $resolvedTemplate = $this->templates->resolve($template);

        return $this->renderResolvedTemplate($resolvedTemplate, $template, $payload);
    }

    public function renderComponent(ComponentInterface|string $component, array|ViewData $data = []): string
    {
        $component = $this->componentLoader->resolve($component);

        return $this->renderTemplate($component, $this->componentLoader->mergeData($component, $data));
    }

    public function addPath(string|Stringable $path): static
    {
        $this->templates->addPath($path);

        return $this;
    }

    /**
     * @return list<string>
     */
    public function paths(): array
    {
        return $this->templates->paths();
    }

    public function components(): ComponentRegistryInterface
    {
        return $this->componentLoader->registry();
    }

    public function loader(): TwigTemplateLoader
    {
        return $this->templates;
    }

    public function environment(): Environment
    {
        return $this->environment;
    }

    public function options(): TwigRendererOptions
    {
        return $this->options;
    }

    private function renderResolvedTemplate(string $resolvedTemplate, TemplateInterface $template, ViewData $data): string
    {
        try {
            return $this->environment->render($resolvedTemplate, $data->all());
        } catch (Throwable $exception) {
            if ($exception instanceof UIException) {
                throw $exception;
            }

            throw TwigRenderException::forTemplate($template->name(), $exception);
        }
    }

    private function registerComponentFunction(): void
    {
        $name = $this->options->componentFunction;

        try {
            $this->environment->addFunction(new TwigFunction(
                $name,
                fn (ComponentInterface|string $component, array|ViewData $data = []): string
                    => $this->renderComponent($component, $data),
                ['is_safe' => ['html']],
            ));
        } catch (LogicException $exception) {
            throw new TwigConfigurationException(
                'Unable to register Twig component function "' . $name . '": ' . $exception->getMessage(),
                0,
                $exception,
            );
        }
    }

    /**
     * @param array<string|int, mixed> $options
     */
    private function isOptionsArray(array $options): bool
    {
        if (array_is_list($options)) {
            return false;
        }

        $optionKeys = [
            'auto_reload',
            'autoReload',
            'auto_escape',
            'autoescape',
            'cache',
            'charset',
            'component_function',
            'componentFunction',
            'debug',
            'environment',
            'environment_options',
            'environmentOptions',
            'optimizations',
            'paths',
            'strict_variables',
            'strictVariables',
            'template_paths',
            'templatePaths',
        ];

        return array_intersect(array_keys($options), $optionKeys) !== [];
    }
}
