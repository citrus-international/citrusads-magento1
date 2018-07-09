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
    private $resource;
    private $readConnection;

    public function setUp()
    {
        $app = Mage::app('default');
        $this->model = new Citrus_Integration_Helper_Data;
        $this->resource = Mage::getSingleton('core/resource');
        $this->readConnection = $this->resource->getConnection('core_read');
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

    public function testGetOrderData() {

        $order = Mage::getModel(Mage_Sales_Model_Order::class)->getCollection()
            ->addAttributeToSelect('*')
            ->getFirstItem();

        $data = $this->model->getOrderData($order);
        $this->assertNotNull($data);
        $this->assertNotNull($data['orderItems']);
        $this->assertTrue(count($data['orderItems']) >= 0);
        $this->assertNotNull($data['orderDate']);
    }

    public function testHandleAdsResponse() {
        $context = $this->getContext();
        $response = Mage::getModel('citrusintegration/service_request')->requestingAnAd($context);
        $return = $this->model->handleAdsResponse($response, $context['pageType']);
        $type = gettype($return);
        $this->assertNotNull($return);
        switch ($type) {
            case "boolean":
                $this->assertTrue($return);
                break;
            case "array" :
                $this->assertNotEmpty($return);
                $this->assertTrue(count($return) == 2);
                $this->assertTrue(count($return['ads']) > 0);
                $this->assertTrue(count($return['banners']) > 0);
                break;
            default:
                break;
        }

        $context = $this->getContext("magento");
        $response = Mage::getModel('citrusintegration/service_request')->requestingAnAd($context);
        $return = $this->model->handleAdsResponse($response, $context['pageType']);
        $type = gettype($return);
        $this->assertNotNull($return);
        switch ($type) {
            case "boolean":
                $this->assertTrue($return);
                break;
            case "array" :
                $this->assertNotEmpty($return);
                $this->assertTrue(count($return['ads']) > 0);
                break;
            default:
                break;
        }
    }

    public function testHandleResponse() {
        $order = Mage::getModel(Mage_Sales_Model_Order::class)->getCollection()
            ->addAttributeToSelect('*')
            ->getFirstItem();

        $data = $this->model->getOrderData($order);
        $body[] = $data;
        $response = Mage::getModel('citrusintegration/service_request')->pushOrderRequest($body);
        $this->assertTrue($response['success']);
        $this->model->handleResponse($response, 'order');
        $data = json_decode($response['message'], true);
        foreach ($data['orders'] as $key => $orderItem){
            $orderTable = $this->resource->getTableName('citrusintegration/order');
            $orderId = $orderItem['id'];
            $query = 'SELECT * FROM ' . $orderTable. ' WHERE citrus_id = ":order_id"';
            $binds = array(
                "order_id" => $orderId
            );
            $orders = $this->readConnection->query($query, $binds);

//            $orderCollection = Mage::getModel('citrusintegration/order')->getCollection()->addAttributeToFilter('citrus_id', $orderItem['id']);
            $this->assertTrue(count($orders) == 1); // Only put this one to the DB
        }
    }

    public function testGetCategoryHierarchies() {
        $oneProductCollection = Mage::getModel(Mage_Catalog_Model_Product::class)->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('entity_id', "237")
            ->getFirstItem();
        $categoryIds = $oneProductCollection->getResource()->getCategoryIds($oneProductCollection);
        $catModel = Mage::getModel('catalog/category')->setStoreId(Mage::app()->getStore()->getId());
        if (is_array($categoryIds)) {
            foreach ($categoryIds as $categoryId) {
                $category = $catModel->load($categoryId);
                $data['categoryHierarchies'][] = $this->model->getCategoryHierarchies($category);
            }
        }

        // Do assertions here
//        var_dump($data['categoryHierarchies']);
        $this->assertNotNull($data['categoryHierarchies']);
        $this->assertNotEmpty($data['categoryHierarchies']);
        $this->assertEquals(2, count($data['categoryHierarchies']));
        foreach ($data['categoryHierarchies'] as $item) {
            $this->assertTrue(in_array("Men", $item));
            $this->assertTrue(in_array("Shirts", $item) || in_array("Sale", $item));
        }
    }

    public function testGetProductFilter() {
        $oneProductCollection = Mage::getModel(Mage_Catalog_Model_Product::class)->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('entity_id', "237")
            ->getFirstItem();
        $filter = $this->model->getProductFilter($oneProductCollection);
        $this->assertNotNull($filter);
        $this->assertNotEmpty($filter);
        $this->assertTrue(in_array("1", $filter));
        $this->assertTrue(in_array("2", $filter));
        $this->assertTrue(in_array("Men", $filter));
        $this->assertTrue(in_array("Shirts", $filter));
        $this->assertTrue(in_array("Sale", $filter));

        $anotherProductCollection = Mage::getModel(Mage_Catalog_Model_Product::class)->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('entity_id', "249")
            ->getFirstItem();
        $filter = $this->model->getProductFilter($anotherProductCollection);
        $this->assertNotNull($filter);
        $this->assertNotEmpty($filter);
        $this->assertTrue(in_array("1", $filter));
        $this->assertTrue(in_array("2", $filter));
        $this->assertTrue(in_array("Men", $filter));
        $this->assertTrue(in_array("Tees, Knits and Polos", $filter));
    }

    private function getContext($search = null) {
        $context = array(
            'catalogId' => $this->model->getCitrusCatalogId(),
            'maxNumberOfAds' => 3
        );

        if (isset($search)) {
            $context['searchTerm'] = $search;
            $context['pageType'] = "Category";
        } else {
            $context['bannerSlotIds'] = ["HOMEPAGE-1","HOMEPAGE-1"];
            $context['productFilters'] = [["1","Men","Shirts"]];
            $context['pageType'] = "Search";
        }

        return $context;
    }
}
