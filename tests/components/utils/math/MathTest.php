<?php

class MathTest extends \PHPUnit\Framework\TestCase
{
    public function testSum()
    {
        $mathUtil = \SwFwLess\components\utils\math\Math::create();

        $testArr = range(1, 1000);
        $this->assertEquals(
            array_sum($testArr),
            $mathUtil->sum($testArr)
        );

        $testArr = range(1, 150000);
        $this->assertEquals(
            doubleval(array_sum($testArr)),
            $mathUtil->sum($testArr)
        );

        $mathUtil = \SwFwLess\components\utils\math\Math::create([
            'sum_ffi_min_count' => 200000,
        ]);

        $testArr = range(1, 300000);
        $this->assertEquals(
            doubleval(array_sum($testArr)),
            $mathUtil->sum($testArr)
        );
    }

    public function testVectorAdd()
    {
        $mathUtil = \SwFwLess\components\utils\math\Math::create();

        $arrCount = 100000;

        $testArr1 = range(1, $arrCount);
        $testArr2 = range(1, $arrCount);
        $sum = [];

        $vector1 = $mathUtil->createCFloatNumbers(4);
        $vector2 = $mathUtil->createCFloatNumbers(4);

        for ($i = 0; $i < $arrCount; $i = $i + 4) {
            $vector1[0] = $testArr1[$i];
            $vector1[1] = $testArr1[$i + 1];
            $vector1[2] = $testArr1[$i + 2];
            $vector1[3] = $testArr1[$i + 3];

            $vector2[0] = $testArr2[$i];
            $vector2[1] = $testArr2[$i + 1];
            $vector2[2] = $testArr2[$i + 2];
            $vector2[3] = $testArr2[$i + 3];

            $sumVector = $mathUtil->vectorAdd($vector1, $vector2, 4);
            foreach ($sumVector as $elementSum) {
                $sum[] = $elementSum;
            }
        }

        foreach ($sum as $i => $value) {
            $this->assertEquals(floatval(2 * ($i + 1)), $value);
        }
    }

    public function testVectorSub()
    {
        $mathUtil = \SwFwLess\components\utils\math\Math::create();

        $arrCount = 100000;

        $testArr1 = range(1, $arrCount);
        $testArr2 = range(1, $arrCount);
        $diff = [];

        $vector1 = $mathUtil->createCFloatNumbers(4);
        $vector2 = $mathUtil->createCFloatNumbers(4);

        for ($i = 0; $i < $arrCount; $i = $i + 4) {
            $vector1[0] = $testArr1[$i];
            $vector1[1] = $testArr1[$i + 1];
            $vector1[2] = $testArr1[$i + 2];
            $vector1[3] = $testArr1[$i + 3];

            $vector2[0] = $testArr2[$i];
            $vector2[1] = $testArr2[$i + 1];
            $vector2[2] = $testArr2[$i + 2];
            $vector2[3] = $testArr2[$i + 3];

            $diffVector = $mathUtil->vectorSub($vector1, $vector2, 4);
            foreach ($diffVector as $elementDiff) {
                $diff[] = $elementDiff;
            }
        }

        foreach ($diff as $i => $value) {
            $this->assertEquals(floatval(0), $value);
        }
    }

    public function testVectorMul()
    {
        $mathUtil = \SwFwLess\components\utils\math\Math::create();

        $arrCount = 4096; // less than 4097 (error)

        $testArr1 = range(1, $arrCount);
        $testArr2 = range(1, $arrCount);
        $product = [];

        $vector1 = $mathUtil->createCFloatNumbers(4);
        $vector2 = $mathUtil->createCFloatNumbers(4);

        for ($i = 0; $i < $arrCount; $i = $i + 4) {
            $vector1[0] = $testArr1[$i];
            $vector1[1] = $testArr1[$i + 1];
            $vector1[2] = $testArr1[$i + 2];
            $vector1[3] = $testArr1[$i + 3];

            $vector2[0] = $testArr2[$i];
            $vector2[1] = $testArr2[$i + 1];
            $vector2[2] = $testArr2[$i + 2];
            $vector2[3] = $testArr2[$i + 3];

            $productVector = $mathUtil->vectorMul($vector1, $vector2, 4);
            foreach ($productVector as $elementProduct) {
                $product[] = $elementProduct;
            }
        }

        foreach ($product as $i => $value) {
            $this->assertEquals(floatval(pow($i + 1, 2)), $value);
        }
    }

    public function testVectorDiv()
    {
        $mathUtil = \SwFwLess\components\utils\math\Math::create();

        $arrCount = 100000;

        $testArr1 = range(1, $arrCount);
        $testArr2 = range(1, $arrCount);
        $div = [];

        $vector1 = $mathUtil->createCFloatNumbers(4);
        $vector2 = $mathUtil->createCFloatNumbers(4);

        for ($i = 0; $i < $arrCount; $i = $i + 4) {
            $vector1[0] = $testArr1[$i];
            $vector1[1] = $testArr1[$i + 1];
            $vector1[2] = $testArr1[$i + 2];
            $vector1[3] = $testArr1[$i + 3];

            $vector2[0] = $testArr2[$i];
            $vector2[1] = $testArr2[$i + 1];
            $vector2[2] = $testArr2[$i + 2];
            $vector2[3] = $testArr2[$i + 3];

            $divVector = $mathUtil->vectorDiv($vector1, $vector2, 4);
            foreach ($divVector as $elementDiv) {
                $div[] = $elementDiv;
            }
        }

        foreach ($div as $i => $value) {
            $this->assertEquals(1.0, $value);
        }
    }

    public function testVectorSqrt()
    {
        $mathUtil = \SwFwLess\components\utils\math\Math::create();

        $arrCount = 100000;

        $testArr1 = range(1, $arrCount);
        $root = [];

        $vector1 = $mathUtil->createCFloatNumbers(4);

        for ($i = 0; $i < $arrCount; $i = $i + 4) {
            $vector1[0] = pow($testArr1[$i], 2);
            $vector1[1] = pow($testArr1[$i + 1], 2);
            $vector1[2] = pow($testArr1[$i + 2], 2);
            $vector1[3] = pow($testArr1[$i + 3], 2);

            $rootVector = $mathUtil->vectorSqrt($vector1, 4);
            foreach ($rootVector as $elementRoot) {
                $root[] = $elementRoot;
            }
        }

        foreach ($root as $i => $value) {
            $this->assertEquals(floatval($i + 1), $value);
        }
    }

    public function testVectorCmp()
    {
        $mathUtil = \SwFwLess\components\utils\math\Math::create();

        $arrCount = 100000;

        $testArr1 = range(1, $arrCount);
        $testArr2 = range(1, $arrCount);
        $comparison = [];

        $vector1 = $mathUtil->createCFloatNumbers(4);
        $vector2 = $mathUtil->createCFloatNumbers(4);

        for ($i = 0; $i < $arrCount; $i = $i + 4) {
            $vector1[0] = $testArr1[$i] + 1;
            $vector1[1] = $testArr1[$i + 1] + 1;
            $vector1[2] = $testArr1[$i + 2] + 1;
            $vector1[3] = $testArr1[$i + 3] + 1;

            $vector2[0] = $testArr2[$i];
            $vector2[1] = $testArr2[$i + 1];
            $vector2[2] = $testArr2[$i + 2];
            $vector2[3] = $testArr2[$i + 3];

            $cmpVector = $mathUtil->vectorCmp($vector1, $vector2, 4);
            foreach ($cmpVector as $elementCmp) {
                $comparison[] = $elementCmp;
            }
        }

        foreach ($comparison as $value) {
            $this->assertNan($value);
        }
    }

    public function testVectorAbs()
    {
        $mathUtil = \SwFwLess\components\utils\math\Math::create();

        $arrCount = 100000;

        $testArr1 = range(1, $arrCount);
        $absRes = [];

        $vector1 = $mathUtil->createCIntNumbers(4);

        for ($i = 0; $i < $arrCount; $i = $i + 4) {
            $vector1[0] = $testArr1[$i];
            $vector1[1] = -1 * $testArr1[$i + 1];
            $vector1[2] = $testArr1[$i + 2];
            $vector1[3] = -1 * $testArr1[$i + 3];

            $absVector = $mathUtil->vectorAbs($vector1, 4);
            foreach ($absVector as $elementAbs) {
                $absRes[] = $elementAbs;
            }
        }

        foreach ($absRes as $i => $value) {
            $this->assertEquals($i + 1, $value);
        }
    }

    public function testVectorCeil()
    {
        $mathUtil = \SwFwLess\components\utils\math\Math::create();

        $arrCount = 100000;

        $testArr1 = range(1, $arrCount);
        $ceilRes = [];

        $vector1 = $mathUtil->createCFloatNumbers(4);

        for ($i = 0; $i < $arrCount; $i = $i + 4) {
            $vector1[0] = $testArr1[$i] + 0.3;
            $vector1[1] = $testArr1[$i + 1] + 0.3;
            $vector1[2] = $testArr1[$i + 2] + 0.3;
            $vector1[3] = $testArr1[$i + 3] + 0.3;

            $ceilVector = $mathUtil->vectorCeil($vector1, 4);
            foreach ($ceilVector as $elementCeil) {
                $ceilRes[] = $elementCeil;
            }
        }

        foreach ($ceilRes as $i => $value) {
            $this->assertEquals(floatval($i + 1 + 1), $value);
        }
    }

    public function testVectorFloor()
    {
        $mathUtil = \SwFwLess\components\utils\math\Math::create();

        $arrCount = 100000;

        $testArr1 = range(1, $arrCount);
        $floorRes = [];

        $vector1 = $mathUtil->createCFloatNumbers(4);

        for ($i = 0; $i < $arrCount; $i = $i + 4) {
            $vector1[0] = $testArr1[$i] + 0.5;
            $vector1[1] = $testArr1[$i + 1] + 0.5;
            $vector1[2] = $testArr1[$i + 2] + 0.5;
            $vector1[3] = $testArr1[$i + 3] + 0.5;

            $floorVector = $mathUtil->vectorFloor($vector1, 4);
            foreach ($floorVector as $elementFloor) {
                $floorRes[] = $elementFloor;
            }
        }

        foreach ($floorRes as $i => $value) {
            $this->assertEquals(floatval($i + 1), $value);
        }
    }

    public function testVectorRound()
    {
        $mathUtil = \SwFwLess\components\utils\math\Math::create();

        $arrCount = 100000;

        $testArr1 = range(1, $arrCount);

        $roundRes = [];

        $vector1 = $mathUtil->createCFloatNumbers(4);

        for ($i = 0; $i < $arrCount; $i = $i + 4) {
            $vector1[0] = $testArr1[$i] + 0.5;
            $vector1[1] = $testArr1[$i + 1] + 0.5;
            $vector1[2] = $testArr1[$i + 2] + 0.5;
            $vector1[3] = $testArr1[$i + 3] + 0.5;

            $roundVector = $mathUtil->vectorRound($vector1, 4);
            foreach ($roundVector as $elementRound) {
                $roundRes[] = $elementRound;
            }
        }

        foreach ($roundRes as $i => $value) {
            $this->assertGreaterThanOrEqual(floatval($i + 1), $value);
        }

        $roundRes = [];

        $vector1 = $mathUtil->createCFloatNumbers(4);

        for ($i = 0; $i < $arrCount; $i = $i + 4) {
            $vector1[0] = $testArr1[$i] + 0.3;
            $vector1[1] = $testArr1[$i + 1] + 0.3;
            $vector1[2] = $testArr1[$i + 2] + 0.3;
            $vector1[3] = $testArr1[$i + 3] + 0.3;

            $roundVector = $mathUtil->vectorRound($vector1, 4);
            foreach ($roundVector as $elementRound) {
                $roundRes[] = $elementRound;
            }
        }

        foreach ($roundRes as $i => $value) {
            $this->assertGreaterThanOrEqual(floatval($i + 1), $value);
        }
    }

    public function testBcAdd()
    {
        $this->assertEquals(100.0, \SwFwLess\components\utils\math\Math::create()->bcadd(
            99.9, 0.1, 1
        ));
    }

    public function testBcSub()
    {
        $this->assertEquals(99.9, \SwFwLess\components\utils\math\Math::create()->bcsub(
            100.0, 0.1, 1
        ));
    }

    public function testBcMul()
    {
        $this->assertEquals(10.0, \SwFwLess\components\utils\math\Math::create()->bcmul(
            100.0, 0.1, 1
        ));
    }

    public function testBcDiv()
    {
        $this->assertEquals(3.0, \SwFwLess\components\utils\math\Math::create()->bcdiv(
            99.9, 33.3, 1
        ));
    }
}
