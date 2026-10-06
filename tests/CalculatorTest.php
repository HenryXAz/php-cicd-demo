<?php
declare(strict_types=1);

namespace Tests;

use App\Calculator;
use PHPUnit\Framework\TestCase;

final class CalculatorTest extends TestCase
{
    public function testAddition() : void
    {
        $calculator = new Calculator();
        $result = $calculator->add(2, 3);

        $this->assertSame(5, $result);
    }

    public function testSubstraction() : void
    {
        $calculator = new Calculator();
        $result = $calculator->substract(10, 4);

        $this->assertSame(6, $result);
    }
}
