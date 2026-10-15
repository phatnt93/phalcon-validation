<?php

declare(strict_types=1);

namespace PhalconValidation\Core;

use PhalconValidation\Contracts\FilterInterface;
use PhalconValidation\Contracts\FilterRegistryInterface;
use PhalconValidation\Filters\BasicTagsFilter;
use PhalconValidation\Filters\BooleanFilter;
use PhalconValidation\Filters\CallbackFilter;
use PhalconValidation\Filters\HtmlencodeFilter;
use PhalconValidation\Filters\LowercaseFilter;
use PhalconValidation\Filters\MsWordCharactersFilter;
use PhalconValidation\Filters\NoiseWordsFilter;
use PhalconValidation\Filters\NormalizeFilesFilter;
use PhalconValidation\Filters\RmpunctuationFilter;
use PhalconValidation\Filters\SanitizeEmailFilter;
use PhalconValidation\Filters\SanitizeFloatsFilter;
use PhalconValidation\Filters\SanitizeNumbersFilter;
use PhalconValidation\Filters\SanitizeStringFilter;
use PhalconValidation\Filters\SlugFilter;
use PhalconValidation\Filters\TrimFilter;
use PhalconValidation\Filters\UppercaseFilter;
use PhalconValidation\Filters\UrlencodeFilter;
use PhalconValidation\Filters\WholeNumberFilter;

/**
 * Registry holding all available sanitization filters.
 */
class FilterRegistry implements FilterRegistryInterface
{
    /**
     * @var array<string, FilterInterface>
     */
    private array $filters = [];

    public function __construct()
    {
        $this->registerDefaults();
    }

    /**
     * Register all built-in filters.
     */
    private function registerDefaults(): void
    {
        $this->register(new TrimFilter());
        $this->register(new SanitizeStringFilter());
        $this->register(new SanitizeEmailFilter());
        $this->register(new SanitizeNumbersFilter());
        $this->register(new SanitizeFloatsFilter());
        $this->register(new BooleanFilter());
        $this->register(new BasicTagsFilter());
        $this->register(new WholeNumberFilter());
        $this->register(new MsWordCharactersFilter());
        $this->register(new LowercaseFilter());
        $this->register(new UppercaseFilter());
        $this->register(new SlugFilter());
        $this->register(new UrlencodeFilter());
        $this->register(new HtmlencodeFilter());
        $this->register(new RmpunctuationFilter());
        $this->register(new NoiseWordsFilter());
        $this->register(new NormalizeFilesFilter('normalize_files'));
        $this->register(new NormalizeFilesFilter('normalize_file'));
    }

    public function register(FilterInterface $filter): self
    {
        $this->filters[$filter->getName()] = $filter;
        return $this;
    }

    public function addFilter(string $name, callable $filter): self
    {
        $this->filters[$name] = new CallbackFilter($name, $filter);
        return $this;
    }

    public function get(string $name): ?FilterInterface
    {
        return $this->filters[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return isset($this->filters[$name]);
    }

    public function all(): array
    {
        return $this->filters;
    }
}
