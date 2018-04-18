<?php

class Citrus_Integration_Adminhtml_Citrusintegration_QueueController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->_title($this->__('Queue List'));
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->renderLayout();
    }
    /**
     * @return false|Citrus_Integration_Model_Service_Request
     */
    protected function getRequestModel(){
        return Mage::getModel('citrusintegration/service_request');
    }
    /**
     * @return false|Mage_Catalog_Model_Product
     */
    protected function getProductModel(){
        return Mage::getModel('catalog/product');
    }
    /**
     * @return false|Mage_Customer_Model_Customer
     */
    protected function getCustomerModel(){
        return Mage::getModel("customer/customer");
    }
    /**
     * @return false|Mage_Sales_Model_Order
     */
    protected function getOrderModel(){
        return Mage::getModel('sales/order');
    }
    /**
     * @param $name string
     * @return false|string
     */
    protected function getCitrusCatalogId($name = null){
        $model = Mage::getModel('citrusintegration/catalog');
        if($name)
            return $model->getCatalogIdByName($name);
        return $model->getCatalogId();
    }
    /**
     * @return false|Citrus_Integration_Helper_Data
     */
    protected function getHelper(){
        return Mage::helper('citrusintegration/data');
    }
    /**
     * @return false|Citrus_Integration_Model_Queue
     */
    protected function getQueueModel(){
        return Mage::getModel('citrusintegration/queue');
    }
    public function productAction(){
        $enable = Mage::getStoreConfig('citrus_sync/citrus_product/enable', Mage::app()->getStore());
        $catalogId = $this->getCitrusCatalogId();
        $teamId = $this->getHelper()->getTeamId();
        if(!$catalogId || !$teamId){
            $message = Mage::helper('adminhtml')->__('Please save your api key first!');
        }
        else {
            if ($enable) {
                /** @var Mage_Catalog_Model_Product $productModel */
                $productModel = $this->getProductModel();
                $allCollections = $productModel->getCollection()
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter('type_id', ['in' => ['simple', 'virtual']])
                    ->addAttributeToFilter('status', 1)
                    ->joinField(
                        'qty',
                        'cataloginventory/stock_item',
                        'qty',
                        'product_id=entity_id',
                        '{{table}}.stock_id=1',
                        'left'
                    )
                    ->setPageSize(100);
                $numberOfPages = $allCollections->getLastPageNumber();
                for ($i = 1; $i <= $numberOfPages; $i++) {
                    $collections = $allCollections->setCurPage($i);
                    foreach ($collections as $collection) {
                        $this->pushItemToQueue($collection);
                    }
                }
                $message = Mage::helper('adminhtml')->__('All Products have been added to queue, click here to go to check out sync queue');
            }
            else {
                $message = Mage::helper('adminhtml')->__('Please enable sync product!');
            }
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(['message'=>$message]));
    }
    public function orderAction(){
        $enable = Mage::getStoreConfig('citrus_sync/citrus_order/enable', Mage::app()->getStore());
        $status = Mage::getStoreConfig('citrus_sync/citrus_order/type_order', Mage::app()->getStore());
        $catalogId = $this->getCitrusCatalogId();
        $teamId = $this->getHelper()->getTeamId();
        if(!$catalogId || !$teamId){
            $message = Mage::helper('adminhtml')->__('Please save your api key first!');
        }
        else {
            if ($enable) {
                /** @var Mage_Catalog_Model_Product $productModel */
                $orderModel = $this->getOrderModel();
                if($status != ''){
                    $allCollections = $orderModel->getCollection()
                        ->addAttributeToFilter('status', array('eq' => $status))
                        ->setPageSize(100);
                }
                else {
                    $allCollections = $orderModel->getCollection()
                        ->setPageSize(100);
                }
                $numberOfPages = $allCollections->getLastPageNumber();
                for ($i = 1; $i <= $numberOfPages; $i++) {
                    $collections = $allCollections->setCurPage($i);
                    foreach ($collections as $collection) {
                        $this->pushItemToQueue($collection);
                    }
                }
                $message = Mage::helper('adminhtml')->__('All Orders have been added to queue, click here to go to check out sync queue');
            }
            else {
                $message = Mage::helper('adminhtml')->__('Please enable sync order!');
            }
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(['message'=>$message]));
    }
    public function customerAction(){
        $enable = Mage::getStoreConfig('citrus_sync/citrus_customer/enable', Mage::app()->getStore());
        $catalogId = $this->getCitrusCatalogId();
        $teamId = $this->getHelper()->getTeamId();
        if(!$catalogId || !$teamId){
            $message = Mage::helper('adminhtml')->__('Please save your api key first!');
        }
        else {
            if ($enable) {
                /** @var Mage_Catalog_Model_Product $productModel */
                $customerModel = $this->getCustomerModel();
                $allCollections = $customerModel->getCollection()
                    ->addAttributeToSelect('*')
                    ->setPageSize(100);
                $numberOfPages = $allCollections->getLastPageNumber();
                for ($i = 1; $i <= $numberOfPages; $i++) {
                    $collections = $allCollections->setCurPage($i);
                    foreach ($collections as $collection) {
                        $this->pushItemToQueue($collection);
                    }
                }
                $message = Mage::helper('adminhtml')->__('All Customers have been added to queue, click here to go to check out sync queue');
            }
            else {
                $message = Mage::helper('adminhtml')->__('Please enable sync customer!');
            }
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(['message'=>$message]));
    }
    public function pushItemToQueue($item){
        $queueModel = $this->getQueueModel();
        $queueCollection = $queueModel->getCollection()->addFieldToSelect('id')
            ->addFieldToFilter('entity_id', ['eq' => $item->getId()])
            ->addFieldToFilter('type', ['eq' => $item->getResourceName()])
            ->getFirstItem();
        if($queueCollection->getData()){
            $queueModel->load($queueCollection->getId());
            $queueModel->enqueue($item->getId(), $item->getResourceName());
        }else {
            $queueModel->enqueue($item->getId(), $item->getResourceName());
        }
    }
    public function syncAction()
    {
        $requestIds = $this->getRequest()->getParam('id');
        $requestIds = [9];
        if(!is_array($requestIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select request(s)'));
        } else {
            try {
                foreach ($requestIds as $requestId) {
                    $requestData = Mage::getModel('citrusintegration/queue')->load($requestId);
                    $this->handleData($requestData->getEntityId(), $requestData->getType());
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                        'Total of %d record(s) were successfully synced', count($requestIds)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/');
    }
    public function handleData($itemId, $type){
        $itemModel = Mage::getModel($type);
        $entity = $itemModel->load($itemId);
        $helper = $this->getHelper();
        switch ($type){
            case 'catalog/product':
                /** @var  $entity Mage_Catalog_Model_Product */
                $body = $helper->getProductData($entity);
                $response = $this->getRequestModel()->pushCatalogProductsRequest($body);
                break;
            case 'customer/customer':
                /** @var  $entity Mage_Customer_Model_Customer */
                $body = $helper->getCustomerData($entity);
                $response = $this->getRequestModel()->pushCustomerRequest($body);
                break;
            case 'sales/order':
                /** @var  $entity Mage_Sales_Model_Order */
                $body = $helper->getOrderData($entity);
                $response = $this->getRequestModel()->pushOrderRequest($body);
                break;
        }
        $this->handleResponse($response);
    }
    protected function handleResponse($response){
        $x = 1;
    }
    public function pushProducts(){

        $enable = Mage::getStoreConfig('citrus_sync/citrus_group/push_current_product', Mage::app()->getStore());
        $catalogId = $this->getCitrusCatalogId();
        $teamId = $this->getHelper()->getTeamId();
        if(!$catalogId || !$teamId){
            $error = Mage::helper('adminhtml')->__('Please save your api key first!');
            Mage::throwException($error);
        }
        else {
            if ($enable) {
                /** @var Mage_Catalog_Model_Product $productModel */
                $productModel = Mage::getModel('catalog/product');
                $allCollections = $productModel->getCollection()
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter('type_id', ['in' => ['simple', 'virtual']])
                    ->addAttributeToFilter('status', 1)
                    ->joinField(
                        'qty',
                        'cataloginventory/stock_item',
                        'qty',
                        'product_id=entity_id',
                        '{{table}}.stock_id=1',
                        'left'
                    )
                    ->setPageSize(100);
                $numberOfPages = $allCollections->getLastPageNumber();
                for ($i = 1; $i <= $numberOfPages; $i++) {
                    $collections = $allCollections->setCurPage($i);
                    $body = [];
                    foreach ($collections as $collection) {
                        $tags = $this->getProductTags($collection->getId());
                        $data['catalogId'] = $catalogId;
                        $data['teamId'] = $teamId;
                        $data['gtin'] = $collection->getId();
                        $data['name'] = $collection->getName();
                        if ($collection->getImage() != 'no_selection')
                            $data['images'] = [Mage::getModel('catalog/product_media_config')->getMediaUrl($collection->getImage())];
                        $data['inventory'] = (int)$collection->getQty();
                        $data['price'] = (int)$collection->getPrice();
                        $data['tags'] = $tags;
                        $categoryIds = $collection->getCategoryIds();
                        $catModel = Mage::getModel('catalog/category')->setStoreId(Mage::app()->getStore()->getId());
                        if (is_array($categoryIds))
                            foreach ($categoryIds as $categoryId) {
                                $category = $catModel->load($categoryId);
                                $data['categoryHierarchy'][] = $category->getName();
                            }
                        $body[] = $data;
                    }
                    $response = $this->getRequestModel()->pushCatalogProductsRequest($body, $catalogId);
                    $this->handleResponse($response);
                }
            }
        }
    }

    public function massDeleteAction() {
        $requestIds = $this->getRequest()->getParam('id');
        if(!is_array($requestIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select request(s)'));
        } else {
            try {
                foreach ($requestIds as $requestId) {
                    $RequestData = Mage::getModel('citrusintegration/queue')->load($requestId);
                    $RequestData->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                        'Total of %d record(s) were successfully deleted', count($requestIds)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/');
    }

    public function messageAction()
    {
        $data = Mage::getModel('citrusintegration/queue')->load($this->getRequest()->getParam('id'));
        echo $data->getContent();
    }

    /**
     * Initialize action
     *
     * Here, we set the breadcrumbs and the active menu
     *
     * @return Mage_Adminhtml_Controller_Action
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('citrus/citrus_queue')
            ->_title($this->__('Sales'))->_title($this->__('Queue'))
            ->_addBreadcrumb($this->__('Sales'), $this->__('Sales'))
            ->_addBreadcrumb($this->__('Baz'), $this->__('Baz'));

        return $this;
    }

    /**
     * Check currently called action by permissions for current user
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('citrus/citrus_queue');
    }
}