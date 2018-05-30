<?php

class Citrus_Integration_Adminhtml_Citrusintegration_QueueController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->_title($this->__('Queue List'));
        $this->loadLayout();
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
        $catalogId = $this->getHelper()->getCitrusCatalogId();
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
        $catalogId = $this->getHelper()->getCitrusCatalogId();
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
        $enable = Mage::getStoreConfig('citrus_sync/citrus_order/enable', Mage::app()->getStore());
        $catalogId = $this->getHelper()->getCitrusCatalogId();
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
            ->addFieldToFilter('entity_id', ['in' => [$item->getId(), $item->getIncrementId()]])
            ->addFieldToFilter('type', ['eq' => $item->getResourceName()])
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
    public function massSyncAction()
    {
        $requestIds = $this->getRequest()->getParam('id');
        if(!is_array($requestIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select request(s)'));
        } else {
            try {
                $queueModel = Mage::getModel('citrusintegration/queue');
                $syncItems = new Varien_Object;
                $catalog_product=[];
                $sales_order=[];
                $customer_customer=[];
                foreach ($requestIds as $key => $requestId) {
                    $requestData = $queueModel->load($requestId);
                    $type = $requestData->getType();
                    if($type == 'catalog/product')
                        $catalog_product[] = $requestData->getEntityId();
                    elseif($type == 'sales/order')
                        $sales_order[] = $requestData->getEntityId();
                    elseif($type == 'customer/customer')
                        $customer_customer[] = $requestData->getEntityId();
                    $queueModel->delete();
                }
                $syncItems->addData(['catalog_product' => $catalog_product]);
                $syncItems->addData(['sales_order' => $sales_order]);
                $syncItems->addData(['customer_customer' => $customer_customer]);
                $this->pushSyncItem($syncItems);
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

    protected function pushSyncItem($syncItems){
        $catalog_product = $syncItems->getCatalogProduct();
        $sales_order = $syncItems->getSalesOrder();
        $customer_customer = $syncItems->getCustomerCustomer();
        if($catalog_product){
            $bodyCatalogProducts = [];
            $bodyProducts = [];
            /** @var Mage_Catalog_Model_Product $productModel */
            $productModel = Mage::getModel(Mage_Catalog_Model_Product::class);
            foreach ($catalog_product as $productId){
                /** @var Mage_Catalog_Model_Product $product */
                $product = $productModel->load($productId);
                $catalogProductData = $this->getHelper()->getCatalogProductData($product);
                foreach ($catalogProductData as $key => $oneData){
                    $bodyCatalogProducts[$key] = array_merge(isset($bodyCatalogProducts[$key]) ? $bodyCatalogProducts[$key] : $bodyCatalogProducts[$key] = [], [$oneData]);
                }
                $bodyProducts[] = $this->getHelper()->getProductData($product);

            }

            foreach ($bodyCatalogProducts as $bodyCatalogProduct){
                $pageCatalogProduct = (int)(count($bodyCatalogProduct)/100);
                for ($i = 0;$i <= $pageCatalogProduct; $i++){
                    $bodyCatalogProductsPage = array_slice($bodyCatalogProduct, $i*100, 100);
                    $responseCatalogProduct = $this->getRequestModel()->pushCatalogProductsRequest($bodyCatalogProductsPage);
                    $this->getHelper()->log('sync catalog product: '.$responseCatalogProduct['message'], __FILE__, __LINE__);
                    $this->getHelper()->log('sync catalog product body: '.json_encode($bodyCatalogProductsPage), __FILE__, __LINE__);
                }
            }
            $pageProduct = (int)(count($bodyProducts)/100);
            for ($i = 0;$i <= $pageProduct; $i++){
                $bodyProductsPage = array_slice($bodyProducts, $i*100, 100);
                $responseProduct = $this->getRequestModel()->pushProductsRequest($bodyProductsPage);
                $this->getHelper()->log('sync product: '.$responseProduct['message'], __FILE__, __LINE__);
                $this->getHelper()->log('sync product body: '.json_encode($bodyProductsPage), __FILE__, __LINE__);
            }
        }
        if($sales_order){
            $body = [];
            /** @var Mage_Sales_Model_Order $orderModel */
            $orderModel = Mage::getModel(Mage_Sales_Model_Order::class);
            foreach ($sales_order as $orderIncrementId){
                /** @var Mage_Sales_Model_Order $order */
                $order = $orderModel->loadByIncrementId($orderIncrementId);
                $data = $this->getHelper()->getOrderData($order);
                $body[] = $data;
            }
            $response = $this->getRequestModel()->pushOrderRequest($body);
            $this->getHelper()->handleResponse($response, 'order', $sales_order);
            $this->getHelper()->log('sync sales order: '.$response['message'], __FILE__, __LINE__);
        }
        if($customer_customer){
            $body = [];
            $customerModel = Mage::getModel(Mage_Customer_Model_Customer::class);
            foreach ($customer_customer as $customerId){
                /** @var Mage_Customer_Model_Customer $customer */
                $customer = $customerModel->load($customerId);
                $data = $this->getHelper()->getCustomerData($customer);
                $body[] = $data;
            }
            $response = $this->getRequestModel()->pushCustomerRequest($body);
            $this->getHelper()->handleResponse($response, 'customer', $customer_customer);
            $this->getHelper()->log('sync customer: '.$response['message'], __FILE__, __LINE__);
        }
    }

    public function pushProducts(){

        $enable = Mage::getStoreConfig('citrus_sync/citrus_group/push_current_product', Mage::app()->getStore());
        $catalogId = $this->getHelper()->getCitrusCatalogId();
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
                        $categoryIds = $collection->getResource()->getCategoryIds($collection);
                        $catModel = Mage::getModel('catalog/category')->setStoreId(Mage::app()->getStore()->getId());
                        if (is_array($categoryIds))
                            foreach ($categoryIds as $categoryId) {
                                $category = $catModel->load($categoryId);
                                $data['categoryHierarchy'][] = $category->getName();
                            }
                        $body[] = $data;
                    }
                    $response = $this->getRequestModel()->pushCatalogProductsRequest([$body]);
                    $this->getHelper()->log('sync catalog product 1: '.$response['message'], __FILE__, __LINE__);
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
            ->_setActiveMenu('citrus')
            ->_title($this->__('Citrus'))->_title($this->__('Queue'));

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