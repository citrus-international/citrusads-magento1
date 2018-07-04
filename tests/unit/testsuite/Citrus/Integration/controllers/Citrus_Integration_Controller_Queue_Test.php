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

    public function testProductAction() {
        $allCollections = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('type_id', array('in' => array('simple', 'virtual')))
            ->addAttributeToFilter('status', 1);
        $count = count($allCollections);

        // Add all products to queue
        $this->mockProductAction();

        $queueCollection = Mage::getModel('citrusintegration/queue')->getCollection();
        $this->assertEquals($count, count($queueCollection));
    }

//    public function testPushSyncItem() {
//        // Add all products to queue
//        $this->mockProductAction();
//
//        $catalog_product = Mage::getModel('citrusintegration/queue')->getCollection()
//            ->addFieldToSelect('entity_id')
//            ->addFieldToFilter('type', 'catalog/product')
//            ->setPageSize(100)
//            ->setCurPage(1);
//
//        $syncItems = new Varien_Object;
//        $syncItems->addData(array('catalog_product' => $catalog_product));
//        $return = $this->model->pushSyncItem($syncItems);
//
//        $this->assertTrue($return['success']);
//    }


    private function mockProductAction()
    {
        $productModel = Mage::getModel('catalog/product');
        /** @var Mage_Catalog_Model_Resource_Product_Collection $allCollections */
        $allCollections = $productModel->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('type_id', array('in' => array('simple', 'virtual')))
            ->addAttributeToFilter('status', 1);
        $allCollections->getItems();
        foreach ($allCollections as $collection) {
            $this->mockPushItemToQueue($collection);
        }
    }

    public function mockPushItemToQueue($item)
    {
        $queueModel = Mage::getModel('citrusintegration/queue');
        $queueCollection = $queueModel->getCollection()->addFieldToSelect('id')
            ->addFieldToFilter('entity_id', array('in' => array($item->getId(), $item->getIncrementId())))
            ->addFieldToFilter('type', array('eq' => $item->getResourceName()))
            ->getFirstItem();
        if($queueCollection->getData()){
            $queueModel->load($queueCollection->getId());
            if($item->getResourceName() == 'sales/order')
                $queueModel->enqueue($item->getIncrementId(), $item->getResourceName());
            else
                $queueModel->enqueue($item->getId(), $item->getResourceName());
        }else {
            if($item->getResourceName() == 'sales/order')
                $queueModel->enqueue($item->getIncrementId(), $item->getResourceName());
            else
                $queueModel->enqueue($item->getId(), $item->getResourceName());
        }
    }
}
