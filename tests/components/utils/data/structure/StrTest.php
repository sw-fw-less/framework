<?php

namespace SwFwLessTest\components\utils\data\structure;

use SwFwLess\components\utils\data\structure\Str;
use SwFwLess\components\utils\data\structure\variable\MetasyntacticVars;

class StrTest extends \PHPUnit\Framework\TestCase
{
    public function testContains()
    {
        $this->assertTrue(Str::contains('abc', ''));
        $this->assertTrue(Str::contains('abc', 'a'));
        $this->assertTrue(Str::contains('abc', 'b'));
        $this->assertTrue(Str::contains('abc', 'c'));
        $this->assertTrue(Str::contains('abc', 'ab'));
        $this->assertTrue(Str::contains('abc', 'bc'));
        $this->assertTrue(Str::contains('abc', 'abc'));
        $this->assertFalse(Str::contains('abc', 'd'));
        $this->assertFalse(Str::contains('abc', 'ac'));

        $this->assertTrue(Str::contains('abc', 'A', 0, false));
        $this->assertTrue(Str::contains('abc', 'B', 0, false));
        $this->assertTrue(Str::contains('abc', 'C', 0, false));
        $this->assertTrue(Str::contains('abc', 'Ab', 0, false));
        $this->assertTrue(Str::contains('abc', 'AB', 0, false));
        $this->assertTrue(Str::contains('abc', 'aB', 0, false));
        $this->assertTrue(Str::contains('abc', 'Bc', 0, false));
        $this->assertTrue(Str::contains('abc', 'BC', 0, false));
        $this->assertTrue(Str::contains('abc', 'bC', 0, false));
        $this->assertTrue(Str::contains('abc', 'Abc', 0, false));
        $this->assertTrue(Str::contains('abc', 'ABc', 0, false));
        $this->assertTrue(Str::contains('abc', 'ABC', 0, false));
        $this->assertTrue(Str::contains('abc', 'aBc', 0, false));
        $this->assertTrue(Str::contains('abc', 'aBC', 0, false));
        $this->assertTrue(Str::contains('abc', 'abC', 0, false));
        $this->assertFalse(Str::contains('abc', 'D', 0, false));
        $this->assertFalse(Str::contains('abc', 'Ac', 0, false));
        $this->assertFalse(Str::contains('abc', 'AC', 0, false));
        $this->assertFalse(Str::contains('abc', 'aC', 0, false));

        $this->assertTrue(Str::contains('abc', '', 0));
        $this->assertTrue(Str::contains('abc', '', 1));
        $this->assertTrue(Str::contains('abc', '', 2));
        $this->assertTrue(Str::contains('abc', 'a', 0));
        $this->assertFalse(Str::contains('abc', 'a', 1));
        $this->assertFalse(Str::contains('abc', 'a', 2));
        $this->assertTrue(Str::contains('abc', 'b', 0));
        $this->assertTrue(Str::contains('abc', 'b', 1));
        $this->assertFalse(Str::contains('abc', 'b', 2));
        $this->assertTrue(Str::contains('abc', 'c', 0));
        $this->assertTrue(Str::contains('abc', 'c', 1));
        $this->assertTrue(Str::contains('abc', 'c', 2));
        $this->assertFalse(Str::contains('abc', 'c', 3));
        $this->assertTrue(Str::contains('abc', 'ab', 0));
        $this->assertFalse(Str::contains('abc', 'ab', 1));
        $this->assertTrue(Str::contains('abc', 'bc', 0));
        $this->assertTrue(Str::contains('abc', 'bc', 1));
        $this->assertFalse(Str::contains('abc', 'bc', 2));
        $this->assertTrue(Str::contains('abc', 'abc', 0));
        $this->assertFalse(Str::contains('abc', 'abc', 1));

        $this->assertTrue(Str::contains('你好，世界', ''));
        $this->assertTrue(Str::contains('你好，世界', '你'));
        $this->assertTrue(Str::contains('你好，世界', '好'));
        $this->assertTrue(Str::contains('你好，世界', '世'));
        $this->assertTrue(Str::contains('你好，世界', '界'));
        $this->assertTrue(Str::contains('你好，世界', '，'));
        $this->assertTrue(Str::contains('你好，世界', '你好'));
        $this->assertTrue(Str::contains('你好，世界', '世界'));
        $this->assertFalse(Str::contains('你好，世界', '！'));
        $this->assertFalse(Str::contains('你好，世界', '好世界'));

        $this->assertTrue(Str::contains('你好，世界 Hello World', '世界'));
        $this->assertTrue(Str::contains('你好，世界 Hello World', '世界 Hello'));
    }

    public function testSplit()
    {
        $this->assertTrue(
            Str::split('abcdef', '')
            === ['a', 'b', 'c', 'd', 'e', 'f']
        );
        $this->assertTrue(
            Str::split('a@b@c@d@e@f', '@')
            === ['a', 'b', 'c', 'd', 'e', 'f']
        );
    }

    public function testStartWith()
    {
        $this->assertTrue(Str::startWith(MetasyntacticVars::FOO, ''));
        $this->assertTrue(
            Str::startWith(
                MetasyntacticVars::FOO . MetasyntacticVars::BAR,
                MetasyntacticVars::FOO
            )
        );
        $this->assertFalse(
            Str::startWith(
                MetasyntacticVars::FOO . MetasyntacticVars::BAR,
                MetasyntacticVars::BAR
            )
        );
    }

    public function testEndWith()
    {
        $this->assertTrue(Str::endWith(MetasyntacticVars::FOO, ''));
        $this->assertTrue(
            Str::endWith(
                MetasyntacticVars::FOO . MetasyntacticVars::BAR,
                MetasyntacticVars::BAR
            )
        );
        $this->assertFalse(
            Str::endWith(
                MetasyntacticVars::FOO . MetasyntacticVars::BAR,
                MetasyntacticVars::FOO
            )
        );
    }
}
