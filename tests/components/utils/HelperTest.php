<?php

class HelperTest extends \PHPUnit\Framework\TestCase
{
    protected static $testSetArr;

    protected static $testForgetArr;

    public function testNestedArrGet()
    {
        $arr = ['a' => ['b' => 'c', 'g' => 'h'], 'd' => ['e' => 'f', 'i' => 'j']];
        $this->assertEquals('h', \SwFwLess\components\Helper::nestedArrGet($arr, 'a.g'));
    }

    public function testNestedArrSet()
    {
        $arr = ['a' => ['b' => 'c', 'g' => 'h'], 'd' => ['e' => 'f', 'i' => 'j']];
        \SwFwLess\components\Helper::nestedArrSet($arr, 'a.k', 'l');
        $this->assertEquals(['a' => ['b' => 'c', 'g' => 'h', 'k' => 'l'], 'd' => ['e' => 'f', 'i' => 'j']], $arr);

        static::$testSetArr = ['a' => ['b' => 'c', 'g' => 'h'], 'd' => ['e' => 'f', 'i' => 'j']];
        \SwFwLess\components\Helper::nestedArrSet(static::$testSetArr, 'a.k', 'l');
        $this->assertEquals(['a' => ['b' => 'c', 'g' => 'h', 'k' => 'l'], 'd' => ['e' => 'f', 'i' => 'j']], static::$testSetArr);
        static::$testSetArr = null;
    }

    public function testNestedArrForget()
    {
        $arr = ['a' => ['b' => 'c', 'g' => 'h'], 'd' => ['e' => 'f', 'i' => 'j']];
        \SwFwLess\components\Helper::nestedArrForget($arr, 'd.e');
        $this->assertEquals(['a' => ['b' => 'c', 'g' => 'h'], 'd' => ['i' => 'j']], $arr);

        static::$testForgetArr = ['a' => ['b' => 'c', 'g' => 'h'], 'd' => ['e' => 'f', 'i' => 'j']];
        \SwFwLess\components\Helper::nestedArrForget(static::$testForgetArr, 'd.e');
        $this->assertEquals(['a' => ['b' => 'c', 'g' => 'h'], 'd' => ['i' => 'j']], static::$testForgetArr);
        static::$testForgetArr = null;
    }
}
