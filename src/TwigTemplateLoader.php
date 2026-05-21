<?php

declare(strict_types=1);

namespace CommonPHP\Drivers\UI\Twig;

use CommonPHP\Drivers\UI\Twig\Exceptions\TwigTemplateNotFoundException;
use CommonPHP\UI\Contracts\TemplateInterface;
use Stringable;
use Twig\Error\LoaderError;
use Twig\Loader\LoaderInterface;
use Twig\Source;

final class TwigTemplateLoader implements LoaderInterface
{
    /**
     * @var list<string>
     */
    private array $paths = [];

    /**
     * @param iterable<string|Stringable> $paths
     */
    public function __construct(iterable $paths = [])
    {
        foreach ($paths as $path) {
            $this->addPath($path);
        }
    }

    public function addPath(string|Stringable $path): static
    {
        $path = trim((string) $path);

        if ($path !== '') {
            $this->paths[] = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
            $this->paths = array_values(array_unique($this->paths));
        }

        return $this;
    }

    /**
     * @return list<string>
     */
    public function paths(): array
    {
        return $this->paths;
    }

    public function getSourceContext(string $name): Source
    {
        $path = $this->find($name);
        $source = file_get_contents($path);

        if ($source === false) {
            throw new LoaderError('Unable to read Twig template "' . $name . '".');
        }

        return new Source($source, $name, $path);
    }

    public function getCacheKey(string $name): string
    {
        return $this->find($name);
    }

    public function isFresh(string $name, int $time): bool
    {
        $modifiedAt = filemtime($this->find($name));

        return $modifiedAt !== false && $modifiedAt <= $time;
    }

    public function exists(string $name): bool
    {
        return $this->find($name, false) !== null;
    }

    public function resolve(TemplateInterface|string $template): string
    {
        $name = $template instanceof TemplateInterface ? $template->name() : $template;
        $path = $template instanceof TemplateInterface ? $template->path() : null;
        $searched = [];
        $resolved = $this->findCandidate($this->candidatePaths($name, $path), $searched);

        if ($resolved === null) {
            throw TwigTemplateNotFoundException::forTemplate($name, $searched);
        }

        return $resolved;
    }

    private function find(string $name, bool $throw = true): ?string
    {
        $searched = [];
        $resolved = $this->findCandidate($this->candidatePaths($name), $searched);

        if ($resolved !== null || !$throw) {
            return $resolved;
        }

        throw new LoaderError('Twig template "' . $name . '" was not found. Searched: ' . implode(', ', $searched));
    }

    /**
     * @param iterable<string> $candidates
     * @param list<string> $searched
     */
    private function findCandidate(iterable $candidates, array &$searched): ?string
    {
        foreach ($candidates as $candidate) {
            $candidate = $this->normalizePath($candidate);
            $searched[] = $candidate;

            if (is_file($candidate)) {
                return $candidate;
            }
        }

        $searched = array_values(array_unique($searched));

        return null;
    }

    /**
     * @return list<string>
     */
    private function candidatePaths(string $name, ?string $explicitPath = null): array
    {
        $candidates = [];

        if ($explicitPath !== null) {
            $candidates[] = $explicitPath;
        }

        foreach ($this->candidateNames($name) as $candidateName) {
            $candidates[] = $candidateName;

            if ($this->isAbsolutePath($candidateName)) {
                continue;
            }

            $this->assertValidRelativeName($candidateName);

            foreach ($this->paths as $path) {
                $candidates[] = $path . DIRECTORY_SEPARATOR . $candidateName;
            }
        }

        return array_values(array_unique($candidates));
    }

    /**
     * @return list<string>
     */
    private function candidateNames(string $name): array
    {
        $name = trim($name);

        if ($name === '') {
            return [];
        }

        $normalized = $this->normalizePath($name);
        $dotted = str_replace('.', DIRECTORY_SEPARATOR, $normalized);
        $names = [$normalized, $dotted];

        foreach ([$normalized, $dotted] as $candidate) {
            if (!$this->hasTwigExtension($candidate)) {
                $names[] = $candidate . '.twig';
                $names[] = $candidate . '.html.twig';
            }
        }

        return array_values(array_unique($names));
    }

    private function normalizePath(string $path): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    private function hasTwigExtension(string $name): bool
    {
        return str_ends_with($name, '.twig');
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
    }

    private function assertValidRelativeName(string $name): void
    {
        if (str_contains($name, "\0")) {
            throw new LoaderError('Twig template names cannot contain null bytes.');
        }

        $segments = preg_split('/[\\\\\\/]+/', $name) ?: [];

        if (in_array('..', $segments, true)) {
            throw new LoaderError('Twig template names cannot contain parent directory segments.');
        }
    }
}
