<?php
/**
 * Created by PhpStorm.
 * User: siyang
 * Date: 3/07/18
 * Time: 1:34 PM
 */

use PHPUnit\Framework\TestCase;

class Citrus_Integration_Block_ProductTest extends TestCase
{
    public function setUp()
    {
        /* You'll have to load Magento app in any test classes in this method */
        $app = Mage::app('default');

        /* Let's create the block instance for further tests */
        $this->productBlock = new Citrus_Integration_Block_Product_List;

    }

    public function testFirstMethod()
    {
        /*Here goes the assertions for your block first method*/
        $this->assertTrue(true);
    }

    public function testSecondMethod()
    {
        /*Here goes the assertions for your block second method*/
    }

}
