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
        $moduleEnable = Mage::getStoreConfig('citrus/citrus_group/enable', Mage::app()->getStore());
        if($moduleEnable) {
            $enable = Mage::getStoreConfig('citrus_sync/citrus_product/enable', Mage::app()->getStore());
            $catalogId = $this->getHelper()->getCitrusCatalogId();
            $teamId = $this->getHelper()->getTeamId();
            if (!$catalogId || !$teamId) {
                $message = Mage::helper('adminhtml')->__('Please save your api key first!');
            } else {
                if ($enable) {
                    /** @var Mage_Catalog_Model_Product $productModel */
                    $productModel = $this->getProductModel();
                    /** @var Mage_Catalog_Model_Resource_Product_Collection $allCollections */
                    $allCollections = $productModel->getCollection()
                        ->addAttributeToSelect('*')
                        ->addAttributeToFilter('type_id', ['in' => ['simple', 'virtual']])
                        ->addAttributeToFilter('status', 1);
                    $allCollections->getItems();
                    foreach ($allCollections as $collection) {
                        $this->pushItemToQueue($collection);
                    }
                    $message = Mage::helper('adminhtml')->__('All Products have been added to queue, click <a href="' . Mage::helper("adminhtml")->getUrl("adminhtml/citrusintegration_queue/index") . '">here</a> to go to check out sync queue');
                } else {
                    $message = Mage::helper('adminhtml')->__('Please enable sync product!');
                }
            }
        }
        else{
            $message = Mage::helper('adminhtml')->__('Please enable module!');
        }
        $params = $this->getRequest()->getParams();
        if(!$params['redirect']){
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(['message'=>$message]));
        }
        else

            $this->_redirect('*/citrusintegration_queue/index');
    }
    public function orderAction(){
        $moduleEnable = Mage::getStoreConfig('citrus/citrus_group/enable', Mage::app()->getStore());
        if($moduleEnable) {
            $enable = Mage::getStoreConfig('citrus_sync/citrus_order/enable', Mage::app()->getStore());
            $status = Mage::getStoreConfig('citrus_sync/citrus_order/type_order', Mage::app()->getStore());
            $catalogId = $this->getHelper()->getCitrusCatalogId();
            $teamId = $this->getHelper()->getTeamId();
            if (!$catalogId || !$teamId) {
                $message = Mage::helper('adminhtml')->__('Please save your api key first!');
            } else {
                if ($enable) {
                    /** @var Mage_Catalog_Model_Product $productModel */
                    $orderModel = $this->getOrderModel();
                    if ($status != '') {
                        $allCollections = $orderModel->getCollection()
                            ->addAttributeToFilter('status', array('eq' => $status));
                    } else {
                        $allCollections = $orderModel->getCollection();
                    }

                    foreach ($allCollections as $collection) {
                        $this->pushItemToQueue($collection);
                    }
                    $message = Mage::helper('adminhtml')->__('All Orders have been added to queue, click <a href="' . Mage::helper("adminhtml")->getUrl("adminhtml/citrusintegration_queue/index") . '">here</a> to go to check out sync queue');
                } else {
                    $message = Mage::helper('adminhtml')->__('Please enable sync order!');
                }
            }
        }
        else{
            $message = Mage::helper('adminhtml')->__('Please enable module!');
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(['message'=>$message]));
    }
    public function customerAction(){
        $moduleEnable = Mage::getStoreConfig('citrus/citrus_group/enable', Mage::app()->getStore());
        if($moduleEnable) {
            $enable = Mage::getStoreConfig('citrus_sync/citrus_order/enable', Mage::app()->getStore());
            $catalogId = $this->getHelper()->getCitrusCatalogId();
            $teamId = $this->getHelper()->getTeamId();
            if (!$catalogId || !$teamId) {
                $message = Mage::helper('adminhtml')->__('Please save your api key first!');
            } else {
                if ($enable) {
                    /** @var Mage_Catalog_Model_Product $productModel */
                    $customerModel = $this->getCustomerModel();
                    $allCollections = $customerModel->getCollection()
                        ->addAttributeToSelect('*');

                    foreach ($allCollections as $collection) {
                        $this->pushItemToQueue($collection);
                    }
                    $message = Mage::helper('adminhtml')->__('All Customers have been added to queue, click <a href="' . Mage::helper("adminhtml")->getUrl("adminhtml/citrusintegration_queue/index") . '">here</a> to go to check out sync queue');
                } else {
                    $message = Mage::helper('adminhtml')->__('Please enable sync customer!');
                }
            }
        }
        else{
            $message = Mage::helper('adminhtml')->__('Please enable module!');
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
//            $queueModel->load($queueCollection->getId());
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
        $moduleEnable = Mage::getStoreConfig('citrus/citrus_group/enable', Mage::app()->getStore());
        if($moduleEnable) {
            $requestIds = $this->getRequest()->getParam('id');
            if (!is_array($requestIds)) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select request(s)'));
            } else {
                try {
                    /** @var Citrus_Integration_Model_Queue $queueModel */
                    $queueModel = Mage::getModel(Citrus_Integration_Model_Queue::class);

                    $oldItem = $queueModel->getCount();
                    $for = count($requestIds)/100;
                    for($i = 0; $i <= $for; $i ++ ){
                        $tmp_array[] = array_slice($requestIds, $i*100, 100);
                    }
                    array_walk($tmp_array, array($this, 'catalogProductCallback'), $oldItem);
                    $newtem = $queueModel->getCount();
                    $session = Mage::getSingleton('adminhtml/session');
                    if($session->getData('orderMessage')){
                        Mage::getSingleton('adminhtml/session')->addError(
                            Mage::helper('adminhtml')->__(
                                'We encountered a problem syncing the order(s): '.$session->getData('orderMessage')
                            )
                        );
                    }if($session->getData('customerMessage')){
                        Mage::getSingleton('adminhtml/session')->addError(
                            Mage::helper('adminhtml')->__(
                                'We encountered a problem syncing the customer(s): '.$session->getData('customerMessage')
                            )
                        );
                    }if($session->getData('productMessage')){
                        Mage::getSingleton('adminhtml/session')->addError(
                            Mage::helper('adminhtml')->__(
                                'We encountered a problem syncing the product(s): '.$session->getData('productMessage')
                            )
                        );
                    }if($oldItem > $newtem){
                        Mage::getSingleton('adminhtml/session')->addSuccess(
                            Mage::helper('adminhtml')->__(
                                'Total of %d record(s) were successfully synced',$oldItem - $newtem
                            )
                        );
                    }
                } catch (Exception $e) {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                }
            }
        }
        else{
            Mage::getSingleton('adminhtml/session')->addError('Please enable module!');
        }
        $this->_redirect('*/*/');
    }
    public function catalogProductCallback($args, $count, $oldItem){
        $queueModel = Mage::getModel('citrusintegration/queue');
        $session = Mage::getSingleton('adminhtml/session');
        $syncItems = new Varien_Object;
        $catalog_product = $queueModel->getCollection()->addFieldToSelect('entity_id')
            ->addFieldToFilter('id', ['in' => $args])
            ->addFieldToFilter('type', 'catalog/product');
        $sales_order = $queueModel->getCollection()->addFieldToSelect('entity_id')
            ->addFieldToFilter('id', ['in' => $args])
            ->addFieldToFilter('type', 'sales/order');
        $customer_customer = $queueModel->getCollection()->addFieldToSelect('entity_id')
            ->addFieldToFilter('id', ['in' => $args])
            ->addFieldToFilter('type', 'customer/customer');


        $syncItems->addData(['catalog_product' => $catalog_product]);
        $syncItems->addData(['sales_order' => $sales_order]);
        $syncItems->addData(['customer_customer' => $customer_customer]);
        $orderMessage = $session->getData('orderMessage');
        $customerMessage = $session->getData('customerMessage');
        $productMessage = $session->getData('productMessage');
        $return = $this->pushSyncItem($syncItems);
        if($return['success']){
            $queueModel->makeDelete($args);

        }
        else{
            if (!$return['productMessage'])
                $queueModel->makeDelete($args,'catalog/product');
            elseif (!$return['orderMessage'])
                $queueModel->makeDelete($args,'sales/order');
            elseif (!$return['customerMessage'])
                $queueModel->makeDelete($args,'customer/customer');
        }
        $return['orderMessage'] = $orderMessage ? $orderMessage : $return['orderMessage'];
        $return['customerMessage'] = $customerMessage ? $customerMessage : $return['customerMessage'];
        $return['productMessage'] = $productMessage ? $productMessage : $return['productMessage'];
        $session->setData($return);
    }
    protected function pushSyncItem($syncItems){
        $catalog_product = $syncItems->getCatalogProduct();
        $sales_order = $syncItems->getSalesOrder();
        $customer_customer = $syncItems->getCustomerCustomer();
        $productSuccess = true;
        $customerSuccess = true;
        $orderSuccess = true;
        $customerMessage = $productMessage = $orderMessage = 0;
        if($catalog_product){
            $bodyCatalogProducts = [];
            $bodyProducts = [];
            /** @var Mage_Catalog_Model_Product $productModel */
            $productModel = Mage::getModel(Mage_Catalog_Model_Product::class);
            $productIds = [];
            foreach ($catalog_product as $productItem){
                $productIds[] = $productItem->getEntityId();
            }
            if($productIds){
                $productCollection = $productModel->getCollection()->addAttributeToSelect('*')
                    ->addAttributeToFilter('entity_id', ['in' => $productIds]);
                foreach ($productCollection as $product){
                    $catalogProductData = $this->getHelper()->getCatalogProductData($product);
                    foreach ($catalogProductData as $key => $oneData){
                        $bodyCatalogProducts[$key] = array_merge(isset($bodyCatalogProducts[$key]) ? $bodyCatalogProducts[$key] : $bodyCatalogProducts[$key] = [], [$oneData]);
                    }
                    $bodyProducts[] = $this->getHelper()->getProductData($product);
                }
                unset($productCollection);
            }
            foreach ($bodyCatalogProducts as $bodyCatalogProduct){
                $pageCatalogProduct = count($bodyCatalogProduct)/100;
                for ($i = 0;$i <= $pageCatalogProduct; $i++){
                    $bodyCatalogProductsPage = array_slice($bodyCatalogProduct, $i*100, 100);
                    if(!empty($bodyCatalogProductsPage)){
                        $responseCatalogProduct = $this->getRequestModel()->pushCatalogProductsRequest($bodyCatalogProductsPage);//$bodyCatalogProductsPage
                        $this->getHelper()->log('sync catalog product: '.$responseCatalogProduct['message'], __FILE__, __LINE__);
                        $this->getHelper()->log('sync catalog product body: '.json_encode($bodyCatalogProductsPage), __FILE__, __LINE__);
                        if($responseCatalogProduct['success']){
                            $productSuccess = true;
                        }
                        else{
                            $productSuccess = false;
                            $productMessage = $responseCatalogProduct['message'];
                        }
                    }
                }
            }
            $pageProduct = count($bodyProducts)/100;
            for ($i = 0;$i <= $pageProduct; $i++) {
                $bodyProductsPage = array_slice($bodyProducts, $i * 100, 100);
                if (!empty($bodyProductsPage)) {
                    $responseProduct = $this->getRequestModel()->pushProductsRequest($bodyProductsPage);
                    $this->getHelper()->log('sync product: ' . $responseProduct['message'], __FILE__, __LINE__);
                    $this->getHelper()->log('sync product body: ' . json_encode($bodyProductsPage), __FILE__, __LINE__);
                }
            }
        }
        if($sales_order){

            $body = [];
            /** @var Mage_Sales_Model_Order $orderModel */
            $orderModel = Mage::getModel(Mage_Sales_Model_Order::class);
            $orderIncrementId = [];
            foreach ($sales_order as $orderItem){
                $orderIncrementId[] = $orderItem->getEntityId();
            }
            if($orderIncrementId){
                $orderCollection = $orderModel->getCollection()->addAttributeToSelect('*')
                    ->addAttributeToFilter('increment_id', ['in' => $orderIncrementId]);
                foreach ($orderCollection as $order){
                    $data = $this->getHelper()->getOrderData($order);
                    $body[] = $data;
                }
                unset($orderCollection);
            }

            $orderPage = count($body)/100;
            for($i = 0; $i <= $orderPage; $i++){
                $bodyOrdersPage = array_slice($body, $i*100, 100);
                if(!empty($bodyOrdersPage)) {
                    $response = $this->getRequestModel()->pushOrderRequest($bodyOrdersPage);
                    $this->getHelper()->handleResponse($response, 'order', $orderIncrementId);
                    $this->getHelper()->log('sync sales order: ' . $response['message'], __FILE__, __LINE__);
                    if ($response['success']) {
                        $orderSuccess = true;
                    } else {
                        $orderSuccess = false;
                        $orderMessage = $response['message'];
                    }
                }
            }
        }
        if($customer_customer){
            $body = [];
            $customerModel = Mage::getModel(Mage_Customer_Model_Customer::class);
            $customerIds = [];
            foreach ($customer_customer as $customerItem){
                $customerIds[] = $customerItem->getEntityId();
            }
            if($customerIds){
                $customerCollection = $customerModel->getCollection()->addAttributeToSelect('*')
                    ->addAttributeToFilter('entity_id', ['in' => $customerIds]);
                foreach ($customerCollection as $customer){
                    $data = $this->getHelper()->getOrderData($customer);
                    $body[] = $data;
                }
                unset($customerCollection);
            }
            $customerPage = count($body)/100;
            for($i = 0; $i <= $customerPage; $i++){
                $bodyCustomersPage = array_slice($body, $i*100, 100);
                if(!empty($bodyCustomersPage)) {
                    $response = $this->getRequestModel()->pushCustomerRequest($bodyCustomersPage);
                    $this->getHelper()->handleResponse($response, 'customer', $customerIds);
                    $this->getHelper()->log('sync sales order: ' . $response['message'], __FILE__, __LINE__);
                    if ($response['success']) {
                        $customerSuccess = true;
                    } else {
                        $customerSuccess = false;
                        $customerMessage = $response['message'];
                    }
                }
            }
        }
        $return['success'] = $productSuccess && $orderSuccess && $customerSuccess;
        $return['customerMessage'] = $customerMessage;
        $return['productMessage'] = $productMessage;
        $return['orderMessage'] = $orderMessage;
        return $return;
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