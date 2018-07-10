<?php
/**
 * Created by PhpStorm.
 * User: siyang
 * Date: 3/07/18
 * Time: 4:14 PM
 */

use PHPUnit\Framework\TestCase;

class Citrus_Integration_Controller_Queue_Test extends TestCase
{
    private $model;

    public function setUp()
    {
        $app = Mage::app('default');
        $this->model = new Citrus_Integration_Adminhtml_Citrusintegration_QueueController;
    }

    // The method pushSyncItem() is protected
    /*public function testPushSyncItem() {
        $productCollections = $this->getProductsCollection();

        $collections = Mage::getModel('citrusintegration/queue')->getCollection();
        foreach ($collections as $item) {
            $item->delete();
        }

        foreach ($productCollections as $collection) {
            $this->model->pushItemToQueue($collection);
        }

        $catalog_product = Mage::getModel('citrusintegration/queue')->getCollection()
            ->addFieldToSelect('entity_id')
            ->addFieldToFilter('type', 'catalog/product')
            ->setPageSize(100)
            ->setCurPage(1);

        $syncItems = new Varien_Object;
        $syncItems->addData(array('catalog_product' => $catalog_product));
        $return = $this->model->pushSyncItem($syncItems);

        $this->assertTrue($return['success']);
    }*/


    public function testPushItemToQueue()
    {
        $productCollections = $this->getProductsCollection();
        $count = count($productCollections);
        var_dump($count);

        $collections = Mage::getModel('citrusintegration/queue')->getCollection();
        foreach ($collections as $item) {
            $item->delete();
        }
        $this->assertEquals(0, Mage::getModel('citrusintegration/queue')->getCollection()->count());
        var_dump($collections->load()->getSize());

        foreach ($productCollections->getItems() as $collection) {
            $this->model->pushItemToQueue($collection);
        }

        $this->assertEquals($count, Mage::getModel('citrusintegration/queue')->getCollection()->count());
    }

    private function getProductsCollection()
    {
        $productModel = Mage::getModel('catalog/product');
        $allCollections = $productModel->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('type_id', array('in' => array('simple', 'virtual')))
            ->addAttributeToFilter('status', 1);
//        $allCollections->getItems();
        return $allCollections;
    }
}
