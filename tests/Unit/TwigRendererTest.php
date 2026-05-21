<?php

declare(strict_types=1);

namespace CommonPHP\Drivers\UI\Twig\Tests\Unit;

use CommonPHP\Drivers\UI\Twig\Exceptions\TwigConfigurationException;
use CommonPHP\Drivers\UI\Twig\Exceptions\TwigRenderException;
use CommonPHP\Drivers\UI\Twig\Exceptions\TwigTemplateNotFoundException;
use CommonPHP\Drivers\UI\Twig\TwigEnvironmentFactory;
use CommonPHP\Drivers\UI\Twig\TwigRenderer;
use CommonPHP\Drivers\UI\Twig\TwigRendererOptions;
use CommonPHP\Drivers\UI\Twig\TwigTemplate;
use CommonPHP\UI\Component;
use CommonPHP\UI\ComponentRegistry;
use CommonPHP\UI\Contracts\ComponentRegistryInterface;
use CommonPHP\UI\Contracts\RendererInterface;
use CommonPHP\UI\Exceptions\InvalidComponentException;
use CommonPHP\UI\Layout;
use CommonPHP\UI\Template;
use CommonPHP\UI\View;
use CommonPHP\UI\ViewFactory;
use PHPUnit\Framework\TestCase;
use Stringable;
use Twig\Environment;

final class TwigRendererTest extends TestCase
{
    public function testConstructorAddsTemplatePathsAndCreatesAComponentRegistry(): void
    {
        $path = new class($this->templatePath()) implements Stringable {
            public function __construct(private readonly string $path)
            {
            }

            public function __toString(): string
            {
                return $this->path;
            }
        };
        $renderer = new TwigRenderer([$path, ' ', $this->templatePath()]);

        self::assertInstanceOf(RendererInterface::class, $renderer);
        self::assertSame([$this->templatePath()], $renderer->paths());
        self::assertInstanceOf(ComponentRegistryInterface::class, $renderer->components());
    }

    public function testAddPathTrimsNormalizesDeduplicatesAndReturnsRenderer(): void
    {
        $renderer = new TwigRenderer();

        self::assertSame($renderer, $renderer->addPath(' '));
        self::assertSame($renderer, $renderer->addPath($this->templatePath() . DIRECTORY_SEPARATOR));
        self::assertSame($renderer, $renderer->addPath(str_replace('\\', '/', $this->templatePath())));

        self::assertSame([$this->templatePath()], $renderer->paths());
    }

    public function testItRendersTemplatesByDottedNameWithEscapedVariables(): void
    {
        $renderer = new TwigRenderer([$this->templatePath()]);

        self::assertSame(
            'Hello Ada &lt;Admin&gt;',
            trim($renderer->renderTemplate('pages.plain', ['name' => 'Ada <Admin>'])),
        );
    }

    public function testItRendersHtmlTwigNamesAndExplicitTemplateFilePaths(): void
    {
        $renderer = new TwigRenderer([$this->templatePath()]);
        $template = Template::file($this->templatePath() . '/pages/plain.twig', ['name' => 'Ada']);

        self::assertSame('Report Quarterly', trim($renderer->renderTemplate('pages.report', [
            'title' => 'Quarterly',
        ])));
        self::assertSame('Hello Ada', trim($renderer->renderTemplate($template)));
    }

    public function testTemplateDataIsUsedAndRenderDataOverridesIt(): void
    {
        $renderer = new TwigRenderer([$this->templatePath()]);
        $template = new TwigTemplate('pages.plain', ['name' => 'Default']);

        self::assertSame('Hello Default', trim($renderer->renderTemplate($template)));
        self::assertSame('Hello Override', trim($renderer->renderTemplate($template, ['name' => 'Override'])));
    }

    public function testItRendersViewsWithoutLayouts(): void
    {
        $renderer = new TwigRenderer([$this->templatePath()]);
        $view = new View('pages.without-layout', ['title' => 'Standalone']);

        self::assertSame('<section>Standalone</section>', trim($renderer->render($view)));
    }

    public function testItRendersViewsLayoutsAndRegisteredComponents(): void
    {
        $registry = new ComponentRegistry([
            new Component('badge', 'components.badge', ['label' => 'Default']),
        ]);
        $renderer = new TwigRenderer([$this->templatePath()], $registry);
        $view = new View(
            'pages.hello',
            ['title' => 'Hello <Ada>', 'label' => 'Ready'],
            new Layout('layouts.main', ['title' => 'Shell <Site>']),
        );

        self::assertSame(
            "<main data-title=\"Shell &lt;Site&gt;\">\n<h1>Hello &lt;Ada&gt;</h1>\n<span class=\"badge\">Ready</span>\n</main>",
            trim($renderer->render($view)),
        );
    }

    public function testLayoutsCanUseCustomContentKeys(): void
    {
        $renderer = new TwigRenderer([$this->templatePath()]);
        $view = new View(
            'pages.without-layout',
            ['title' => 'Inside'],
            new Layout('layouts.slot', contentKey: 'slot'),
        );

        self::assertSame(
            '<article><section>Inside</section></article>',
            preg_replace('/>\s+</', '><', trim($renderer->render($view))),
        );
    }

    public function testItCanRenderRegisteredAndDirectComponents(): void
    {
        $registry = new ComponentRegistry([
            new Component('badge', 'components.badge', ['label' => 'Default']),
        ]);
        $renderer = new TwigRenderer([$this->templatePath()], $registry);
        $direct = new Component('direct', 'components.badge', ['label' => 'Direct']);

        self::assertSame('<span class="badge">Default</span>', trim($renderer->renderComponent('badge')));
        self::assertSame('<span class="badge">Override</span>', trim($renderer->renderComponent('badge', [
            'label' => 'Override',
        ])));
        self::assertSame('<span class="badge">Direct</span>', trim($renderer->renderComponent($direct)));
    }

    public function testItCreatesEnvironmentsFromOptionsArrays(): void
    {
        $options = TwigRendererOptions::fromArray([
            'paths' => [$this->templatePath()],
            'debug' => true,
            'strict_variables' => true,
            'component_function' => 'ui_component',
        ]);
        $environment = (new TwigEnvironmentFactory())->fromArray([
            'paths' => [$this->templatePath()],
            'debug' => true,
        ]);

        self::assertSame([$this->templatePath()], $options->templatePaths());
        self::assertTrue($options->environmentOptions()['debug']);
        self::assertTrue($options->environmentOptions()['strict_variables']);
        self::assertSame('ui_component', $options->componentFunction);
        self::assertInstanceOf(Environment::class, $environment);
    }

    public function testViewFactoryCanInstantiateTwigRendererAsARuntimeDriver(): void
    {
        $factory = new ViewFactory();
        $factory->setDriver(TwigRenderer::class, [
            'templatePaths' => [$this->templatePath()],
        ]);

        self::assertInstanceOf(TwigRenderer::class, $factory->renderer());
        self::assertSame('Hello Driver', trim($factory->renderTemplate('pages.plain', [
            'name' => 'Driver',
        ])));
    }

    public function testItThrowsForInvalidOptions(): void
    {
        $this->expectException(TwigConfigurationException::class);

        TwigRendererOptions::fromArray(['debug' => 'yes']);
    }

    public function testItThrowsForMissingTemplates(): void
    {
        $renderer = new TwigRenderer([$this->templatePath()]);

        $this->expectException(TwigTemplateNotFoundException::class);
        $this->expectExceptionMessage('missing.template');

        $renderer->renderTemplate('missing.template');
    }

    public function testItThrowsForMissingRegisteredComponents(): void
    {
        $renderer = new TwigRenderer([$this->templatePath()]);

        $this->expectException(InvalidComponentException::class);
        $this->expectExceptionMessage('Component "missing" is not registered.');

        $renderer->renderComponent('missing');
    }

    public function testItWrapsTwigRuntimeFailures(): void
    {
        $renderer = new TwigRenderer(new TwigRendererOptions(
            templatePaths: [$this->templatePath()],
            strictVariables: true,
        ));

        $this->expectException(TwigRenderException::class);
        $this->expectExceptionMessage('pages.strict');

        $renderer->renderTemplate('pages.strict');
    }

    private function templatePath(): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, dirname(__DIR__) . '/Fixtures/templates');
    }
}
