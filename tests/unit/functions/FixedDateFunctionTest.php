<?php

namespace malkusch\phpmock\functions;

/**
 * Tests FixedDateFunction.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license http://www.wtfpl.net/txt/copying/ WTFPL
 * @see FixedDateFunction
 */
class FixedDateFunctionTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Tests getDate().
     *
     * @test
     */
    public function testGetDate()
    {
        $function = new FixedDateFunction(strtotime("2013-3-3"));
        $this->assertEquals("3. 3. 2013", $function->getDate("j. n. Y"));
        $this->assertEquals("24. 3. 2015", $function->getDate("j. n. Y", strtotime("2015-3-24")));
    }
}
