<?php

declare(strict_types=1);

namespace CommonPHP\Drivers\UI\Twig;

use CommonPHP\Drivers\UI\Twig\Exceptions\TwigConfigurationException;
use Stringable;

final class TwigRendererOptions
{
    public const string DEFAULT_COMPONENT_FUNCTION = 'component';

    /**
     * @var list<string>
     */
    private array $templatePaths;

    /**
     * @var array<string, mixed>
     */
    private array $environmentOptions;

    /**
     * @param iterable<string|Stringable> $templatePaths
     * @param string|false|null $cache
     * @param string|bool|callable $autoescape
     * @param array<string, mixed> $environmentOptions
     */
    public function __construct(
        iterable $templatePaths = [],
        public string|false|null $cache = false,
        public bool $debug = false,
        public ?bool $autoReload = null,
        public bool $strictVariables = false,
        mixed $autoescape = 'html',
        public string $charset = 'UTF-8',
        public int $optimizations = -1,
        public string $componentFunction = self::DEFAULT_COMPONENT_FUNCTION,
        array $environmentOptions = [],
    ) {
        $this->templatePaths = self::normalizePaths($templatePaths);
        $this->cache = $this->normalizeCache($cache);
        $this->charset = $this->nonEmptyString($charset, 'charset');
        $this->componentFunction = $this->nonEmptyString($componentFunction, 'componentFunction');

        if (!is_string($autoescape) && !is_bool($autoescape) && !is_callable($autoescape)) {
            throw new TwigConfigurationException('Twig option "autoescape" must be a string, boolean, or callable.');
        }

        $resolvedOptions = array_replace($environmentOptions, [
            'cache' => $this->cache,
            'debug' => $this->debug,
            'strict_variables' => $this->strictVariables,
            'autoescape' => $autoescape,
            'charset' => $this->charset,
            'optimizations' => $this->optimizations,
        ]);

        if ($this->autoReload !== null) {
            $resolvedOptions['auto_reload'] = $this->autoReload;
        }

        $this->environmentOptions = $resolvedOptions;
    }

    /**
     * @param array<string|int, mixed> $options
     */
    public static function fromArray(array $options): self
    {
        if (array_is_list($options)) {
            return new self(templatePaths: $options);
        }

        $environmentOptions = self::arrayOption($options, ['environmentOptions', 'environment_options', 'environment']);

        return new self(
            templatePaths: self::iterableOption($options, ['templatePaths', 'template_paths', 'paths']),
            cache: self::cacheOption($options, ['cache']),
            debug: self::boolOption($options, ['debug'], false),
            autoReload: self::nullableBoolOption($options, ['autoReload', 'auto_reload']),
            strictVariables: self::boolOption($options, ['strictVariables', 'strict_variables'], false),
            autoescape: self::autoescapeOption($options, ['autoescape', 'auto_escape'], 'html'),
            charset: self::stringOption($options, ['charset'], 'UTF-8'),
            optimizations: self::intOption($options, ['optimizations'], -1),
            componentFunction: self::stringOption(
                $options,
                ['componentFunction', 'component_function'],
                self::DEFAULT_COMPONENT_FUNCTION,
            ),
            environmentOptions: $environmentOptions,
        );
    }

    /**
     * @return list<string>
     */
    public function templatePaths(): array
    {
        return $this->templatePaths;
    }

    /**
     * @return array<string, mixed>
     */
    public function environmentOptions(): array
    {
        return $this->environmentOptions;
    }

    /**
     * @param iterable<string|Stringable> $paths
     * @return list<string>
     */
    private static function normalizePaths(iterable $paths): array
    {
        $normalized = [];

        foreach ($paths as $path) {
            if (!is_string($path) && !$path instanceof Stringable) {
                throw new TwigConfigurationException('Twig template paths must be strings.');
            }

            $path = trim((string) $path);

            if ($path === '') {
                continue;
            }

            $normalized[] = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        }

        return array_values(array_unique($normalized));
    }

    private function normalizeCache(string|false|null $cache): string|false
    {
        if ($cache === false || $cache === null) {
            return false;
        }

        $cache = trim($cache);

        return $cache === '' ? false : str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $cache);
    }

    private function nonEmptyString(string $value, string $option): string
    {
        $value = trim($value);

        if ($value === '') {
            throw new TwigConfigurationException('Twig option "' . $option . '" cannot be empty.');
        }

        return $value;
    }

    /**
     * @param array<string|int, mixed> $options
     * @param list<string> $keys
     */
    private static function value(array $options, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $options)) {
                return $options[$key];
            }
        }

        return null;
    }

    /**
     * @param array<string|int, mixed> $options
     * @param list<string> $keys
     */
    private static function stringOption(array $options, array $keys, string $default): string
    {
        $value = self::value($options, $keys);

        if ($value === null) {
            return $default;
        }

        if (!is_string($value)) {
            throw new TwigConfigurationException('Twig option "' . $keys[0] . '" must be a string.');
        }

        return $value;
    }

    /**
     * @param array<string|int, mixed> $options
     * @param list<string> $keys
     */
    private static function intOption(array $options, array $keys, int $default): int
    {
        $value = self::value($options, $keys);

        if ($value === null) {
            return $default;
        }

        if (!is_int($value)) {
            throw new TwigConfigurationException('Twig option "' . $keys[0] . '" must be an integer.');
        }

        return $value;
    }

    /**
     * @param array<string|int, mixed> $options
     * @param list<string> $keys
     */
    private static function boolOption(array $options, array $keys, bool $default): bool
    {
        $value = self::value($options, $keys);

        if ($value === null) {
            return $default;
        }

        if (!is_bool($value)) {
            throw new TwigConfigurationException('Twig option "' . $keys[0] . '" must be a boolean.');
        }

        return $value;
    }

    /**
     * @param array<string|int, mixed> $options
     * @param list<string> $keys
     */
    private static function nullableBoolOption(array $options, array $keys): ?bool
    {
        $value = self::value($options, $keys);

        if ($value === null) {
            return null;
        }

        if (!is_bool($value)) {
            throw new TwigConfigurationException('Twig option "' . $keys[0] . '" must be a boolean or null.');
        }

        return $value;
    }

    /**
     * @param array<string|int, mixed> $options
     * @param list<string> $keys
     * @return iterable<string|Stringable>
     */
    private static function iterableOption(array $options, array $keys): iterable
    {
        $value = self::value($options, $keys);

        if ($value === null) {
            return [];
        }

        if (!is_iterable($value)) {
            throw new TwigConfigurationException('Twig option "' . $keys[0] . '" must be iterable.');
        }

        return $value;
    }

    /**
     * @param array<string|int, mixed> $options
     * @param list<string> $keys
     * @return array<string, mixed>
     */
    private static function arrayOption(array $options, array $keys): array
    {
        $value = self::value($options, $keys);

        if ($value === null) {
            return [];
        }

        if (!is_array($value)) {
            throw new TwigConfigurationException('Twig option "' . $keys[0] . '" must be an array.');
        }

        return $value;
    }

    /**
     * @param array<string|int, mixed> $options
     * @param list<string> $keys
     */
    private static function cacheOption(array $options, array $keys): string|false|null
    {
        $value = self::value($options, $keys);

        if ($value === null || $value === false || is_string($value)) {
            return $value;
        }

        throw new TwigConfigurationException('Twig option "' . $keys[0] . '" must be a string, false, or null.');
    }

    /**
     * @param array<string|int, mixed> $options
     * @param list<string> $keys
     */
    private static function autoescapeOption(array $options, array $keys, mixed $default): mixed
    {
        $value = self::value($options, $keys);

        return $value ?? $default;
    }
}
