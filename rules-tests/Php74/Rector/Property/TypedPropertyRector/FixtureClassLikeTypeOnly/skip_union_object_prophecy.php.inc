<?php

namespace Rector\Tests\Php74\Rector\Property\TypedPropertyRector\FixtureClassLikeTypeOnly;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\PhpUnit\ProphecyTrait;
use Rector\Tests\Php74\Rector\Property\TypedPropertyRector\Source\ReturnString;

class ReturnStringTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ReturnString|ObjectProphecy
     */
    private $obj;

    protected function setUp(): void
    {
        $this->obj = $this->prophesize(ReturnString::class);
    }

    public function testReturnName()
    {
        $this->obj->getName()->willReturn('name')->shouldBeCalled();
    }
}