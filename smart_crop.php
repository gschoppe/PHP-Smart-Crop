<?php
/****************************************************************
  Smart Cropping Class
  Copyright 2014 Greg Schoppe (GPL Licensed)
  http://gschoppe.com
  
  Desc: Takes a GD2 image reference and a target width/height,
        and produces a cropped resized image that puts the focus
        of the image at or close to a rule of thirds line.
  NOTE: THIS CLASS IS A PROOF OF CONCEPT AND RUNS SLOWLY.
        BE SURE TO CACHE RESULTS AND, IF POSSIBLE RUN AS A CHRON,
        BACKGROUND OR AJAX SCRIPT.
 ****************************************************************/
class smart_crop {
    protected $img, $orig_w, $orig_h, $x, $y, $x_weight, $y_weight;
    
    /* constructor - initializes the object
       takes: $img - gd2 image resource */
    function __construct($img) {
        $this->img = $img;
        $this->orig_w = imageSX($img);
        $this->orig_h = imageSY($img);
    }
    
    /* find_focus - identifies the focal point of an image,
                    based on color difference and image entropy
       takes: $slices - integer representing precision of focal
                        point. larger values are slower, but more
                        precise (optional, defaults to 20)
              $weight - float between 0 and 1 representing
                        weighting between entropy method (0) and
                        color method (1) (optional, defaults to .5)
              $sample - integer representing the downsampled
                        resolution of the image to test. larger
                        values are slower, but more precise
                        (optional, defaults to 200) */
    public function find_focus($slices = 20, $weight = .5, $sample=200) {
        // get a sample image to play with
        $temp = $this->rough_in_size($sample, $sample);
        $w = imageSX($temp);
        $h = imageSY($temp);
        // smooth it a little to help reduce the effects of noise
        imagefilter($temp, IMG_FILTER_SMOOTH, 7);
        // get the mean color of the entire image
        $avgColor = $this->average_color($temp,0,0,$w,$h);
        $left = $top = 0;
        //find the horizontal focus position
        $sliceArray   = array();
        // -get the width of each vertical slice
        $slice = round($w/$slices);
        for($i=0;$i<$slices;$i++) {
            if($weight == 0) {
                // -we're skipping this calculation because 
                // -weight 0 doesnt take color into account
                $colorSlice = 0;
            } else {
                // -get the distance from the average color of
                // -the image to the average color of the slice
                $color          = $this->average_color($temp, $i*$slice, 0, $slice, $h);
                $colorSlice     = $this->euclidean_distance($avgColor, $color);
            }
            if($weight == 1) {
                // -we're skipping this calculation because 
                // -weight 1 doesnt take entropy into account
                $entropySlice = 0;
            } else {
                // -get the level of entropy of the slice
                $entropySlice   = $this->get_entropy($temp, $i*$slice, 0, $slice, $h);
            }
            // -get a weighted average of the two values
            $sliceArray[$i] = $colorSlice*$weight + $entropySlice*(1-$weight);
        }
        // -get the array index of the best slice
        $focus   = array_search(max($sliceArray), $sliceArray);
        // -get the pixel value corresponding with the center of that slice
        $x       = ($focus + 0.5)*$slice/$w;
        // figure out which way to weight the image from the focus
        $xWeight = $this->get_array_weight($sliceArray, $focus);
        unset($sliceArray);
        //find the vertical focus position
        $sliceArray   = array();
        // -get the width of each horizontal slice
        $slice = round($h/$slices);
        for($i=0;$i<$slices;$i++) {
            if($weight == 0) {
                // -we're skipping this calculation because 
                // -weight 0 doesnt take color into account
                $colorSlice = 0;
            } else {
                // -get the distance from the average color of
                // -the image to the average color of the slice
                $color          = $this->average_color($temp, 0, $i*$slice, $w, $slice);
                $colorSlice     = $this->euclidean_distance($avgColor, $color);
            }
            if($weight == 1) {
                // -we're skipping this calculation because 
                // -weight 1 doesnt take entropy into account
                $entropySlice = 0;
            } else {
                // -get the level of entropy of the slice
                $entropySlice   = $this->get_entropy($temp, 0, $i*$slice, $w, $slice);
            }
            // -get a weighted average of the two values
            $sliceArray[$i] = $colorSlice*$weight + $entropySlice*(1-$weight);
        }
        // -get the array index of the best slice
        $focus   = array_search(max($sliceArray), $sliceArray);
        // -get the pixel value corresponding with the center of that slice
        $y       = ($focus + 0.5)*$slice/$h;
        // figure out which way to weight the image from the focus
        $yWeight = $this->get_array_weight($sliceArray, $focus);
        // set these values as the focus of the image
        $this->set_focus($x, $y, $xWeight, $yWeight);
    }
    
    /* set_focus - sets the focal point of an image manually
       takes: $x - integer representing the pixel position
                   of the focal point horizontally
              $y - integer representing the pixel position
                   of the focal point vertically
              $xWeight - float from -1 to 1 representing 
                         whether the image is more interesting
                         to the left of the focal point or the
                         right (optional, defaults to 0)
              $yWeight - float from -1 to 1 representing 
                         whether the image is more interesting
                         above the focal point or below
                         (optional, defaults to 0)
       returns: boolean - true for success, false for failure */
    public function set_focus($x, $y, $xWeight=0, $yWeight=0) {
        $w = imageSX($this->img);
        $h = imageSY($this->img);
        // check to make sure these values are valid
        if($x < 0 || $x >= $w || $y < 0 || $y >= $h)
            return false;
        // set the focus point
        $this->x = $x;
        $this->x_weight = $xWeight;
        $this->y = $y;
        $this->y_weight = $yWeight;
        return true;
    }
    /* get_resized   - creates a cropped resized image with the
                       focal point of the image at or close to
                       one of the rule of thirds lines
       takes: $newW   - integer representing the target width of
                        the image to return
              $newH   - integer representing the target height
                        of the image to return
              $slices - integer representing precision of focal
                        point. larger values are slower, but more
                        precise (optional, defaults to 20)
              $weight - float between 0 and 1 representing
                        weighting between entropy method (0) and
                        color method (1) (optional, defaults to .5)
              $sample - integer representing the downsampled
                        resolution of the image to test. larger
                        values are slower, but more precise
                        (optional, defaults to 200)
       returns: GD2 image resource to resized image on success, false
                on failure */
    public function get_resized($newW, $newH, $slices=20, $weight=.5, $sample=200) {
        if($newW < 1 || $newH < 1) return false;
        // scale the image proportionally to cover the area
        $temp = $this->rough_in_size($newW, $newH);
        $w   = imageSX($temp);
        $h   = imageSY($temp);
        // if we're done, skip the rest
        if($w == $newW && $h == $newH) return $temp;
        // if a focus wasn't defined already, do it now
        if(!$this->x || !$this->y) $this->find_focus($slices, $weight, $sample);
        // this is the x and y coords for the corner of the crop
        $x = $y = 0;
        if($w > $newW) {
            // we're cropping width
            if($this->x_weight == 0) {
                // center the image on the focal point
                $x = $this->x*$w - 0.5*$newW;
            } elseif($this->x_weight > 0) {
                // put the focal point on the right rule of thirds line
                $x = $this->x*$w - 2/3*$newW;
            } else {
                // put the focal point on the left rule of thirds line
                $x = $this->x*$w - 1/3*$newW;
            }
            // correct the position to be inside the image's bounds
            if($x >= $w-$newW) $x = $w-$newW-1;
            if($x < 0) $x = 0;
        } else {
            // we're cropping height
            if($this->y_weight == 0) {
                // center the image on the focal point
                $y = $this->y*$h - 0.5*$newH;
            } elseif($this->y_weight > 0) {
                // put the focal point on the top rule of thirds line
                $y = $this->y*$h - 2/3*$newH;
            } else {
                // put the focal point on the bottom rule of thirds line
                $y = $this->y*$h - 1/3*$newH;
            }
            // correct the position to be inside the image's bounds
            if($y >= $h-$newH) $y = $h-$newH-1;
            if($y < 0) $y = 0;
        }
        // make the final cropped image
        $croppedThumb = imagecreatetruecolor($newW,$newH);
        imagealphablending( $croppedThumb, false );
        imagesavealpha( $croppedThumb, true );
        imagecopyresampled($croppedThumb, $temp, 0, 0, $x, $y, $newW, $newH, $newW, $newH);
        imagedestroy($temp);
        return($croppedThumb);
    }
    
    /* rough_in_size  - PROTECTED resizes image proportionally,
                        so that the given width and height are
                        covered. */
    protected function rough_in_size($newW, $newH) {
        $w = $this->orig_w;
        $h = $this->orig_h;
        // image must be valid
        if($w < 1 || $h < 1)
            return false;
        // first proportionally resize dimensions by width dimension
        $tempW = $newW;
        $tempH = ($h*$newW)/$w;
        // if it's too small, try resizing dimensions by height instead
        if($tempH<$newH) {
            $tempW = ($w*$newH)/$h;
            $tempH = $newH;
        }
        // if it's still too small for some reason,
        // just force dimensions to size (in case of rounding errors)
        if($tempW < $newW || $tempH < $newH) {
            $tempW = $newW;
            $tempH = $newH;
        }
        // make the resized image
        $temp = imagecreatetruecolor($tempW, $tempH);
        imagealphablending( $temp, false );
        imagesavealpha( $temp, true );
        imagecopyresampled($temp, $this->img, 0, 0, 0, 0, $tempW, $tempH, $w, $h);
        return($temp);
    }
    
    /* average_color - PROTECTED gets the mean average color of
                       the region of an image, within a bounding
                       box */
    protected function average_color($img,$x,$y,$w,$h) {
        // make a down sampled 1x1px square from the image
        $colorTemp     = imagecreatetruecolor(1,1);
        imagecopyresampled($colorTemp, $img, 0, 0, $x, $y, 1, 1, $w, $h);
        // get the color of that pixel
        $avgColor      = imagecolorsforindex($colorTemp, imagecolorat($colorTemp,0,0));
        imagedestroy($colorTemp);
        return $avgColor;
    }
    
    /* euclidean_distance - PROTECTED gets the euclidean distance
                            (in LAB-X Color space) between two RGB
                            colors */
    protected function euclidean_distance($color1, $color2) {
        // convert colors to LAB-X
        $color1 = $this->RGBtoLAB($color1);
        $color2 = $this->RGBtoLAB($color2);
        // euclidean distance
        $sumOfSquares = 0;
        foreach($color1 as $key=>$val) {
            $sumOfSquares += pow(($color2[$key]-$val),2);
        }
        $distance = sqrt($sumOfSquares);
        // divide by ten to put in similar range to entropy numbers
        return ($distance/10);
    }
    
    /* RGBtoHSV - PROTECTED converts a given color in RGB to HSV
       (yes, I had to google this) */
    protected function RGBtoHSV($color) {
        $R = ($color['red'] / 255);
        $G = ($color['green'] / 255);
        $B = ($color['blue'] / 255);
        $maxRGB = max($R, $G, $B);
        $minRGB = min($R, $G, $B);
        $chroma = $maxRGB - $minRGB;
        $computedV = 100 * $maxRGB;
        if ($chroma == 0)
            return array('h'=>0, 's'=>0, 'v'=>$computedV);
        $computedS = 100 * ($chroma / $maxRGB);
        if ($R == $minRGB) {
            $h = 3 - (($G - $B) / $chroma);
        } elseif ($B == $minRGB) {
            $h = 1 - (($R - $G) / $chroma);
        }else {
            $h = 5 - (($B - $R) / $chroma);
        }
        $computedH = $h*60;
        return array('h'=>$computedH, 's'=>$computedS, 'v'=>$computedV);
    }
    
    /* RGBtoLAB - PROTECTED converts a given color in RGB to LAB-X Color
       (yes, I had to google this) */
    protected function RGBtoLAB($color) {
        $r = $color['red'  ]/255;
        $g = $color['green']/255;
        $b = $color['blue' ]/255;
        if($r > 0.04045) {
            $r = pow((($r + 0.055) / 1.055), 2.4);
        } else {
            $r = $r / 12.92;
        }
        if($g > 0.04045) {
            $g = pow((($g + 0.055) / 1.055), 2.4);
        } else {
            $g = $g / 12.92;
        }
        if($b > 0.04045) {
            $b = pow((($b + 0.055) / 1.055), 2.4);
        } else {
            $b = $b / 12.92;
        }
        $r *= 100;
        $g *= 100;
        $b *= 100;
        $x  = 0.4124*$r + 0.3576*$g + 0.1805*$b;
        $y  = 0.2126*$r + 0.7152*$g + 0.0722*$b;
        $z  = 0.0193*$r + 0.1192*$g + 0.9505*$b;
        $l  = $a = $b = 0;
        if(!($y == 0)) {
            $l = 10*sqrt($y);
            $a = 17.5*((1.02*$x) - $y)/sqrt($y);
            $b = 7*($y - 0.847*$z)/sqrt($y);
        }
        return array('L'=>$l, 'A'=>$a, 'B'=>$b);
    }
    
    /* get_entropy - PROTECTED gets the level of entropy present in a slice of an image */
    protected function get_entropy($img, $x=0, $y=0, $w=null, $h=null) {
        if($w == null) $w = imageSX($img)-$x;
        if($h == null) $h = imageSY($img)-$y;
        if($w < 1 || $h < 1) return false;
        // create a temp image from our slice
        $temp = imagecreatetruecolor($w,$h);
        imagecopy($temp, $img, 0, 0, $x, $y, $w, $h);
        // make that image a greyscale set of detected edges
        imagefilter($temp, IMG_FILTER_EDGEDETECT);
        imagefilter($temp, IMG_FILTER_GRAYSCALE);
        // create a histogram of the edge image
        $levels = array();
        for($x=0;$x<$w;$x++) {
            for($y=0;$y<$h;$y++) {
                $color = imagecolorsforindex($temp, imagecolorat($temp,$x,$y));
                $grayVal = $color['red'];
                if(!isset($levels[$grayVal]))$levels[$grayVal]=0;
                $levels[$grayVal]++;
            }
        }
        // get entropy value from histogram
        $entropy = 0;
        foreach($levels as $level) {
            $pl = $level/($w*$h);
            $pl = $pl*log($pl);
            $entropy -= $pl;
        }
        imagedestroy($temp);
        return($entropy);
    }
    
    /* get_array_weight - PRIVATE tells you if the values to the
       left of the index average higher than the values to the
       right, or vice versa, or if they're equally balanced */
    private function get_array_weight($array, $focus) {
        $slices = count($array);
        $a  = $b = 0;
        if($focus == 0) {
            $a  = 0;
            $b = 1;
        } elseif($focus == $slices-1) {
            $a  = 1;
            $b = 0;
        } else {
            // sum values to the left of focus and get mean average
            for($i=0;$i<$focus;$i++) {
                $a  += $array[$i];
            }
            $a = $a/$focus;
            // sum values to the right of focus and get mean average
            for($i=$focus+1;$i<$slices;$i++) {
                $b += $array[$i];
            }
            $b = $b/($slices-($focus+1));
        }
        if($a > $b) return 1;
        if($a < $b) return -1;
        return 0;
    }
}
