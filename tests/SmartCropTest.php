<?php

/*
 * SmartCrop
 */

/**
 * Test of SmartCrop
 */
class SmartCropTest extends PHPUnit\Framework\TestCase
{

    protected $sut;

    protected function setUp(): void
    {
        $img = imagecreatefromstring(file_get_contents(__DIR__ . '/testimg.jpg'));
        $this->sut = new smart_crop($img);
    }

    public function testCrop()
    {
        $cropped = $this->sut->get_resized(150, 150);
        $this->assertEquals(150, imagesx($cropped));
        $this->assertEquals(150, imagesy($cropped));
    }

}
