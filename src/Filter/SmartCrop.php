<?php

/*
 * Smart Crop
 */

namespace Trismegiste\ImageTools\Entropy\Filter;

use Trismegiste\ImageTools\Entropy\Filter;

require_once __DIR__ . '/smart_crop.php';

/**
 * Crop based on entropy
 */
class SmartCrop implements Filter
{

    protected $newH;
    protected $newW;
    protected $slices;
    protected $weight;
    protected $sample;

    /**
     * Creates a cropped resized image filter with the focal point of the image 
     * at or close to one of the rule of thirds lines
     *
     * @param int $newW integer representing the target width of the image to return
     * @param int $newH integer representing the target height of the image to return
     * @param int $slices integer representing precision of focal point. larger values are slower, but more precise (optional, defaults to 20)
     * @param float $weight float between 0 and 1 representing weighting between entropy method (0) and color method (1) (optional, defaults to .5)
     * @param int $sample integer representing the downsampled resolution of the image to test. larger values are slower, but more precise (optional, defaults to 200)
     */
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
