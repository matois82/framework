<?php
namespace Colibri\Tests\Util;

use Colibri\Util\Arr;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2014-10-24 at 20:08:26.
 */
class ArrTest extends \PHPUnit_Framework_TestCase
{
    public function overwriteDataProvider()
    {
        return [
            [
                ['k1' => 'value1', 'k2' => 'value2'],
                ['k2' => 'v2'],
                ['k1' => 'value1', 'k2' => 'v2'],
            ],
            [
                ['k1' => 'value1', 'k2' => ['k2.1' => 'v2.1', 'k2.2' => 'v2.2']],
                ['k2' => ['k2.2' => 'some new value']],
                ['k1' => 'value1', 'k2' => ['k2.1' => 'v2.1', 'k2.2' => 'some new value']],
            ],
        ];
    }

    /**
     * @covers       \Colibri\Util\Arr::overwrite
     * @dataProvider overwriteDataProvider
     *
     * @param array $original
     * @param array $overwriteWith
     * @param array $expectedResult
     */
    public function testOverwrite(array $original, array $overwriteWith, array $expectedResult)
    {
        $result = Arr::overwrite($original, $overwriteWith);
        $this->assertEquals($expectedResult, $result);
    }

    public function getDataProvider()
    {
        return [
            ['k1.k11', 'value_11'],
            ['k1.k12.k121', 121],
        ];
    }

    /**
     * @dataProvider getDataProvider
     * @covers       \Colibri\Util\Arr::get
     *
     * @param string $key
     * @param mixed  $expectedValue
     */
    public function testGet($key, $expectedValue)
    {
        static $array = [
            'k1' => [
                'k11' => 'value_11',
                'k12' => [
                    'k121' => 121,
                ],
            ],
        ];
        $value = Arr::get($array, $key);
        $this->assertEquals($expectedValue, $value);
    }
}
