<?php

/*
 * Smart Crop
 */

namespace Trismegiste\ImageTools;

/**
 * Contract for a filter on GD2 image ressource
 * @todo Migration to PHP 8 will use GdImage objects
 */
interface Filter
{

    /**
     * Apply a filter on image
     * @param resource $source GD2 image
     * @return resource GD2 image 
     */
    public function apply($source);
}
