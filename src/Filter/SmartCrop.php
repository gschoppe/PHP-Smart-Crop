<?php

/*
 * Smart Crop
 */

namespace Trismegiste\ImageTools\Filter;

/**
 * Crop based on entropy
 */
class SmartCrop implements \Trismegiste\ImageTools\Filter
{

    protected $newH;
    protected $newW;
    protected $slices;
    protected $weight;
    protected $sample;

    public function __construct(int $newW, int $newH, int $slices = 20, float $weight = .5, int $sample = 200)
    {
        $this->newH = $newH;
        $this->newW = $newW;
        $this->sample = $sample;
        $this->slices = $slices;
        $this->weight = $weight;
    }

    public function apply($source)
    {
        $wrapped = new \smart_crop($source);

        return $wrapped->get_resized($this->newW, $this->newH, $this->slices, $this->weight, $this->sample);
    }

}
