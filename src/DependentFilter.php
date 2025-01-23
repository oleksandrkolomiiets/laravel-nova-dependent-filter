<?php

namespace Okol\DependentFilter;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Laravel\Nova\Filters\Filter;
use Laravel\Nova\Http\Requests\NovaRequest;

class DependentFilter extends Filter
{
    /**
     * @var callable|null
     */
    protected $optionsCallback;

    /**
     * @var callable|null
     */
    protected $applyCallback;

    /**
     * @var string[]
     */
    public $dependentOf = [];

    /**
     * Default value.
     *
     * @var mixed
     */
    public $default = '';

    /**
     * @var string
     */
    public $attribute;

    /**
     * @var bool
     */
    public $hideWhenEmpty = false;

    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'dependent-filter';

    public function __construct($name = null, $attribute = null)
    {
        $this->name = $name ?? $this->name;
        $this->attribute = $attribute ?? $this->attribute ?? str_replace(' ', '_', Str::lower($this->name()));
    }

    /**
     * Apply the filter to the given query.
     */
    public function apply(NovaRequest $request, Builder $query, mixed $value): Builder
    {
        if ($this->applyCallback) {
            return call_user_func($this->applyCallback, $request, $query, $value);
        }

        return $query->whereIn($this->attribute, (array) $value);
    }

    /**
     * Get the key for the filter.
     */
    public function key(): string
    {
        return $this->attribute;
    }

    /**
     * Get the filter's available options.
     *
     * @return array<string, string>
     */
    public function options(NovaRequest $request, array $filters = []): array
    {
        return call_user_func($this->optionsCallback, $request, $filters);
    }

    final public function dependentOf($filter): static
    {
        if (! is_array($filter)) {
            $filter = func_get_args();
        }

        $this->dependentOf = $filter;

        return $this;
    }

    final public function getOptions(NovaRequest $request, array $filters = [])
    {
        return collect(
            $this->options($request, $filters + array_fill_keys($this->dependentOf, ''))
        )->map(function ($value, $key) {
            return is_array($value) ? ($value + ['value' => $key]) : ['label' => $value, 'value' => $key];
        })->values()->all();
    }

    final public function withOptions($callback, $dependentOf = null): static
    {
        if (! is_callable($callback)) {
            $callback = function () use ($callback) {
                return $callback;
            };
        }

        $this->optionsCallback = $callback;

        if (! is_null($dependentOf)) {
            $this->dependentOf($dependentOf);
        }

        return $this;
    }

    final public function withDefault($value): static
    {
        $this->default = $value;

        return $this;
    }

    public function default()
    {
        return $this->default;
    }

    final public function withApply(callable $callback)
    {
        $this->applyCallback = $callback;

        return $this;
    }

    public function hideWhenEmpty($value = true): static
    {
        $this->hideWhenEmpty = $value;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return array_merge([
            'class' => $this->key(),
            'name' => $this->name(),
            'component' => $this->component(),
            'options' => count($this->dependentOf) === 0 ? $this->getOptions(app(NovaRequest::class)) : [],
            'currentValue' => $this->default() ?? '',
            'dependentOf' => $this->dependentOf,
            'hideWhenEmpty' => $this->hideWhenEmpty,
        ], $this->meta());
    }

    public static function make(...$args)
    {
        return new static(...$args);
    }
}
