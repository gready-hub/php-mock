<?php

namespace malkusch\phpmock\phpunit;

use malkusch\phpmock\MockBuilder;

/**
 * Adds building a function mock functionality into PHPUnit_Framework_TestCase.
 *
 * Use this trait in your PHPUnit_Framework_TestCase:
 * <code>
 * <?php
 *
 * namespace foo;
 *
 * use malkusch\phpmock\phpunit\PHPMock;
 *
 * class FooTest extends \PHPUnit_Framework_TestCase
 * {
 *
 *     use PHPMock;
 *
 *     public function testBar()
 *     {
 *         $time = $this->getFunctionMock(__NAMESPACE__, "time");
 *         $time->expects($this->once())->willReturn(3);
 *         $this->assertEquals(3, time());
 *     }
 * }
 * </code>
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license WTFPL
 */
trait PHPMock
{

    /**
     * Returns the enabled function mock.
     *
     * This mock will be disabled automatically after the test run.
     *
     * @param string $namespace The function namespace.
     * @param string $name      The function name.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject The PHPUnit mock.
     */
    public function getFunctionMock($namespace, $name)
    {
        $mock = $this->getMock('malkusch\phpmock\phpunit\MockDelegate');
        
        $functionMockBuilder = new MockBuilder();
        $functionMockBuilder->setNamespace($namespace)
                            ->setName($name)
                            ->setFunctionProvider(new MockDelegateFunction($mock));
                
        $functionMock = $functionMockBuilder->build();
        $functionMock->enable();
        
        /**
         * @var \PHPUnit_Framework_TestResult $result Disables the mock automatically.
         */
        $result = $this->getTestResultObject();
        $result->addListener(new MockDisabler($functionMock));
        
        $proxy = new MockObjectProxy($mock);
        return $proxy;
    }
}
