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
        $this->sut = new Trismegiste\ImageTools\Filter\SmartCrop(150, 150);
    }

    public function testCrop()
    {
        $img = imagecreatefromstring(file_get_contents(__DIR__ . '/testimg.jpg'));
        $cropped = $this->sut->apply($img);
        $this->assertEquals(150, imagesx($cropped));
        $this->assertEquals(150, imagesy($cropped));
    }

}
