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
//        var_dump($count);

        $queueModel = Mage::getModel('citrusintegration/queue');
        $collections = $queueModel->getCollection();
        foreach ($collections as $item) {
            $item->delete();
        }
        $this->assertEquals(0, Mage::getModel('citrusintegration/queue')->getCollection()->count());
//        var_dump($collections->load()->getSize());

        foreach ($productCollections->getItems() as $collection) {
            $this->model->pushItemToQueue($queueModel, $collection);
        }
        $this->assertEquals(0, Mage::getModel('citrusintegration/queue')->getCollection()->count());
        $queueModel->commit();
        $this->assertEquals($count, Mage::getModel('citrusintegration/queue')->getCollection()->count());
    }

    public function testCatalogProductCallback() {
        $requestIds = array();

        // Clear queue
        $queueModel = Mage::getModel('citrusintegration/queue');
        $collections = $queueModel->getCollection();
        foreach ($collections as $item) {
            $item->delete();
        }

        // Push 100 products to queue
        $productCollections = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('type_id', array('in' => array('simple', 'virtual')))
            ->addAttributeToFilter('status', 1)
            ->setPageSize(100)
            ->setCurPage(1);
        foreach ($productCollections->getItems() as $collection) {
            $this->model->pushItemToQueue($queueModel, $collection);
        }
        $queueModel->commit();

        // Push 100 customers to queue
        $customerCollections = Mage::getModel(Mage_Customer_Model_Customer::class)->getCollection()
            ->addAttributeToSelect('*')
            ->setPageSize(100)
            ->setCurPage(1);
        foreach ($customerCollections->getItems() as $collection) {
            $this->model->pushItemToQueue($collection);
        }
        $queueModel->commit();

        // Push 100 orders to queue
        $orderCollection = Mage::getModel(Mage_Sales_Model_Order::class)->getCollection()
            ->addAttributeToSelect('*')
            ->setPageSize(100)
            ->setCurPage(1);
        foreach ($orderCollection->getItems() as $collection) {
            $this->model->pushItemToQueue($collection);
        }

        // Get request Ids from the queue
        $queueCollection = Mage::getModel('citrusintegration/queue')->getCollection();
        $requestIds = $queueCollection->getAllIds();

//        count($requestIds);
//        var_dump($requestIds);

        // Call CatalogProductCallback()
        $oldItem = Mage::getModel('citrusintegration/queue')->getCount();
        $this->assertEquals($oldItem, count($requestIds));

        $for = count($requestIds)/100;
        for($i = 0; $i <= $for; $i ++){
            $tmp_array[] = array_slice($requestIds, $i*100, 100);
        }
        array_walk($tmp_array, array($this->model, 'catalogProductCallback'), $oldItem);
        $newtem = Mage::getModel('citrusintegration/queue')->getCount();

        $session = Mage::getSingleton('adminhtml/session');

        // Assertions here
        $this->assertEquals(0, $newtem);
        $this->assertTrue($session->getData('success'));

//        var_dump($session->getData('orderMessage'));
//        var_dump($session->getData('customerMessage'));
//        var_dump($session->getData('productMessage'));

        $this->assertEquals(0, $session->getData('orderMessage'));
        $this->assertEquals(0, $session->getData('customerMessage'));
        $this->assertEquals(0, $session->getData('productMessage'));
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
