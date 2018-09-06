<?php
/**
 * Created by PhpStorm.
 * User: siyang
 * Date: 3/07/18
 * Time: 1:34 PM
 */

use PHPUnit\Framework\TestCase;

class Citrus_Integration_Block_Test extends TestCase
{

    private $product_block;
    private $helper;

    public function setUp()
    {
        /* You'll have to load Magento app in any test classes in this method */
        $app = Mage::app('default');
        $this->product_block = new Citrus_Integration_Block_Product_List;
        $this->helper = new Citrus_Integration_Helper_Data;
    }

    public function testGetLoadedProductCollection()
    {
        $catalogId = $this->helper->getCitrusCatalogId();
        $requestBody = array(
            "catalogId" => $catalogId,
            "pageType" => "Category",
            "maxNumberOfAds" => 3,
            "bannerSlotIds" => ["HOMEPAGE-1", "HOMEPAGE-1"],
            "productFilters" => [["1", "Men", "Shirts"]]
        );

        // Make a request
        $response = $this->helper->getRequestModel()->requestingAnAd($requestBody);

        // Check response
        $this->assertTrue($response['success']);
        $data = json_decode($response['message'], true);
        $this->assertEquals(1, count($data['ads']));
        $this->assertEquals("msj008", $data['ads'][0]['gtin']);
//        echo "gtin = " . $data['ads'][0]['gtin'];
        $this->assertEquals(1, count($data['banners']));
    }
}
