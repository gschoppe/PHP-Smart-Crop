<?php

use PHPUnit\Framework\TestCase;
use Trismegiste\ImageTools\Entropy\Filter;
use Trismegiste\ImageTools\Entropy\FilterComposition;

/*
 * Smart Crop
 */

class FilterCompositionTest extends TestCase
{

    protected $sut;

    protected function setUp(): void
    {
        $filter = $this->createMock(Filter::class);
        $filter->expects($this->atLeastOnce())
                ->method('apply');

        $this->sut = new FilterComposition([$filter]);
    }

    public function testApply()
    {
        $this->sut->apply(null);
    }

}
