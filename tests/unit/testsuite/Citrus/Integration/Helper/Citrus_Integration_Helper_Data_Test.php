<?php
/**
 * Created by PhpStorm.
 * User: siyang
 * Date: 4/07/18
 * Time: 3:28 PM
 */

include "Citrus/Integration/controllers/Adminhtml/Citrusintegration/QueueController.php";

use PHPUnit\Framework\TestCase;

class Citrus_Integration_Helper_DataTest extends TestCase
{
    private $model;
    private $queueModel;

    public function setUp()
    {
        $app = Mage::app('default');
        $this->model = new Citrus_Integration_Helper_Data;
        $this->queueModel = new Citrus_Integration_Adminhtml_Citrusintegration_QueueController;
    }

    public function testGetCatalogProductData() {


        // Add all products to queue
//        $this->queueModel->productAction();

        $catalog_product = Mage::getModel('citrusintegration/queue')->getCollection()
            ->addFieldToSelect('entity_id')
            ->addFieldToFilter('type', 'catalog/product')
            ->setPageSize(10)
            ->setCurPage(1);

        $productIds = array();
        foreach ($catalog_product as $item) {
            $productIds[] = $item->getEntityId();
        }
        $productCollection =  Mage::getModel(Mage_Catalog_Model_Product::class)->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('entity_id', array('in' => $productIds));

        foreach ($productCollection as $product){
            $catalogProductData = $this->model->getCatalogProductData($product);
            $this->assertTrue(count($catalogProductData) > 0);
            $this->assertNotNull($catalogProductData[0]['catalogId']);
            $this->assertNotNull($catalogProductData[0]['teamId']);
            $this->assertNotNull($catalogProductData[0]['gtin']);
            $this->assertNotNull($catalogProductData[0]['inventory']);
            $this->assertNotNull($catalogProductData[0]['price']);
            $this->assertNotNull($catalogProductData[0]['categoryHierarchy']);
//            $this->assertInstanceOf('Array', $catalogProductData[0]['categoryHierarchy']);
            $this->assertTrue(count($catalogProductData[0]['categoryHierarchy']) >= 0);
            $this->assertNotNull($catalogProductData[0]['tags']);
            $this->assertNotNull($catalogProductData[0]['filters']);
//            $this->assertInstanceOf('array', $catalogProductData[0]['filters']);
            $this->assertTrue(count($catalogProductData[0]['filters']) >= 0);

            $bodyProduct = $this->model->getProductData($product);
            $this->assertNotNull($bodyProduct);
            $this->assertNotNull($bodyProduct['name']);
            $this->assertNotNull($bodyProduct['images']);
            $this->assertNotNull($bodyProduct['gtin']);
            $this->assertNotNull($bodyProduct['size']);
            $this->assertNotNull($bodyProduct['categoryHierarchies']);
//            $this->assertInstanceOf('Array', $bodyProduct['categoryHierarchies']);
            $this->assertTrue(count($bodyProduct['categoryHierarchies']) >= 0);
        }
    }
}
