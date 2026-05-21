# Twig UI Driver Usage

The Twig driver implements `CommonPHP\UI\Contracts\RendererInterface`, so it can be used directly or passed into `CommonPHP\UI\ViewFactory`.

## Templates

Template names may be provided as dotted CommonPHP names or normal Twig paths. For example, `pages.dashboard` resolves to `pages/dashboard.twig` or `pages/dashboard.html.twig` inside the configured template paths.

```php
use CommonPHP\Drivers\UI\Twig\TwigRenderer;

$renderer = new TwigRenderer([__DIR__ . '/templates']);

echo $renderer->renderTemplate('pages.dashboard', [
    'title' => 'Dashboard',
]);
```

## Components

Register reusable CommonPHP UI components with a `ComponentRegistry`. Twig templates render them through the `component()` function.

```php
use CommonPHP\Drivers\UI\Twig\TwigRenderer;
use CommonPHP\UI\Component;
use CommonPHP\UI\ComponentRegistry;

$components = new ComponentRegistry([
    new Component('badge', 'components.badge', ['label' => 'Default']),
]);

$renderer = new TwigRenderer([__DIR__ . '/templates'], $components);
```

```twig
{{ component('badge', {'label': 'Ready'}) }}
```

Component output is marked safe for HTML because the component template is responsible for escaping its own variables.

## Layouts

`View` layout handling follows the core UI package contract. The page is rendered first, then inserted into the layout under the layout content key, which defaults to `content`.

```php
use CommonPHP\UI\Layout;
use CommonPHP\UI\View;

$html = $renderer->render(new View(
    'pages.dashboard',
    ['title' => 'Dashboard'],
    new Layout('layouts.app'),
));
```

```twig
<main>
    {{ content|raw }}
</main>
```

## Options

Pass `TwigRendererOptions` or an options array when you need Twig environment settings.

```php
use CommonPHP\Drivers\UI\Twig\TwigRenderer;
use CommonPHP\Drivers\UI\Twig\TwigRendererOptions;

$renderer = new TwigRenderer(new TwigRendererOptions(
    templatePaths: [__DIR__ . '/templates'],
    cache: __DIR__ . '/var/twig',
    debug: true,
    strictVariables: true,
));
```
