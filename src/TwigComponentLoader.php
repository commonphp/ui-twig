<?php

declare(strict_types=1);

namespace CommonPHP\Drivers\UI\Twig;

use CommonPHP\UI\ComponentRegistry;
use CommonPHP\UI\Contracts\ComponentInterface;
use CommonPHP\UI\Contracts\ComponentRegistryInterface;
use CommonPHP\UI\ViewData;

final class TwigComponentLoader
{
    private ComponentRegistryInterface $registry;

    public function __construct(?ComponentRegistryInterface $registry = null)
    {
        $this->registry = $registry ?? new ComponentRegistry();
    }

    public function registry(): ComponentRegistryInterface
    {
        return $this->registry;
    }

    public function register(ComponentInterface $component): static
    {
        $this->registry->register($component);

        return $this;
    }

    public function has(string $name): bool
    {
        return $this->registry->has($name);
    }

    public function resolve(ComponentInterface|string $component): ComponentInterface
    {
        return $component instanceof ComponentInterface ? $component : $this->registry->get($component);
    }

    /**
     * @param array<string, mixed>|ViewData $data
     */
    public function mergeData(ComponentInterface $component, array|ViewData $data = []): ViewData
    {
        return (new ViewData($component->data()->all()))->merge($data);
    }

    /**
     * @return array<string, ComponentInterface>
     */
    public function all(): array
    {
        return $this->registry->all();
    }
}
