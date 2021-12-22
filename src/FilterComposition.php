<?php

/*
 * Smart Crop
 */

namespace Trismegiste\ImageTools;

/**
 * Composition of filters
 */
class FilterComposition implements Filter
{

    protected $filters;

    /**
     * Create a filter composition of filters
     * 
     * Since (g ∘ f )(x) = g(f(x)), filters must be sent in this same order :
     * @example $compo = new FilterComposition([$g, $f]);
     * will apply $f filter and then $g filter
     * 
     * @param array $filters instances of Filter
     */
    public function __construct(array $filters)
    {
        $this->filters = array_reverse($filters);
    }

    public function apply($source)
    {
        $tmp = $source;
        foreach ($this->filters as $filter) {
            $tmp = $filter->apply($tmp);
        }

        return $tmp;
    }

}
