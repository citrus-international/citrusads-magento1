<?php
class Citrus_Integration_Model_Observer
{
    /**
     * @return false|Citrus_Integration_Model_Queue
     */
    protected function getQueueModel(){
        return Mage::getModel('citrusintegration/queue');
    }
    /**
     * @return false|Citrus_Integration_Model_Customer
     */
    protected function getCitrusCustomerModel(){
        return Mage::getModel('citrusintegration/customer');
    }
    /**
     * @return Mage_Core_Helper_Abstract|Citrus_Integration_Helper_Data
     */
    protected function getHelper(){
        return Mage::helper('citrusintegration/data');
    }
    public function addDiscountToProduct($observer){
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getProduct();
        $this->applyDiscount($product);
        return $this;
    }
    public function applyDiscount($product){
        $adModel = Mage::getModel('citrusintegration/ad');
        $discountModel = Mage::getModel('citrusintegration/discount');
        $host = $this->getHelper()->getHost();
        $datetime = new DateTime();
        $now = $datetime->format('Y-m-d\TH:i:s\Z');
        $adCollections = $adModel->getCollection()
            ->addFieldToSelect('*')
            ->addFieldToFilter('host',['eq' => $host])
            ->addFieldToFilter('expiry', ['gteq' => $now]);
        foreach ($adCollections as $adCollection){
            if($adCollection['gtin'] == $product->getId()){
                $discount = $discountModel->load($adCollection->getDiscountId());
                if($product->getFinalPrice() <= $discount->getMinPrice()){
                    continue;
                }else{
                    $newPrice = ($product->getFinalPrice() - (float)$discount->getAmount()) <= $discount->getMinPrice() ? $discount->getMinPrice() : $product->getFinalPrice() - (float)$discount->getAmount();
                    $product->setFinalPrice($newPrice);
                }
            }
        }
    }
    public function applyDiscountListProduct($observer){
        $collections = $observer->getCollection();
        foreach ($collections as $collection){
            $this->applyDiscount($collection);
        }
        return $this;
    }
    public function handleGetResponse($response, $type = null, $param = null){
        $name = $this->getHelper()->getCitrusCatalogName();
        $host = $this->getHelper()->getHost();
        if ($response['success']) {
            if($type == 'catalog'){
                $data = json_decode($response['message'], true);
                if(is_array($data['catalogs'])){
                    $new = true;
                    foreach ($data['catalogs'] as $catalog){
                        /** @var Citrus_Integration_Model_Catalog $model */
                        $model = Mage::getModel('citrusintegration/catalog');
                        $id = $model->getIdByName($name);
                        if(!$id){
                            $catalogData = [
                                'catalog_id' => $catalog['id'],
                                'teamId' => $catalog['teamId'],
                                'host' => $host,
                                'name' => $catalog['name']
                            ];
                            $model->addData($catalogData);
                            try {
                                $model->save();
                            } catch (Exception $e) {

                            }
                        }
                        if($name == $catalog['name']){
                            $new = false;
                        }
                    }
                    if($new){
                        $this->pushCatalog($name);
                    }
                }
                else{
                    $this->pushCatalog($name);
                }
            }
        }
        else {
            $data = json_decode($response['message'], true);
            $error = $data['message'] != '' ? $data['message'] : 'Something went wrong. Please try again in a few minutes';
            $error = Mage::helper('adminhtml')->__($error);
            Mage::throwException($error);
        }
    }
    public function pushCatalog($name){
        $requestModel = $this->getHelper()->getRequestModel();
        $response = $requestModel->pushCatalogsRequest($name);
        $this->getHelper()->handleResponse($response, 'catalog', $name);
    }
    public function createCatalog($observer)
    {
        $enable = Mage::getStoreConfig('citrus/citrus_group/enable', Mage::app()->getStore());
        $catalogName = Mage::getStoreConfig('citrus/citrus_group/catalog_name', Mage::app()->getStore());
        if ($enable) {

            $responseModel = $this->getHelper()->getResponseModel();
            $response = $responseModel->getCatalogListResponse();
            $this->handleGetResponse($response, 'catalog', $catalogName);
        }
    }
    public function createRootCategory($storeId, $name){
        /** @var Mage_Catalog_Model_Category $category */
        $category = Mage::getModel('catalog/category');
        $category->setStoreId($storeId);
        $category->setName($name);
        $category->setIsActive(1);
        $category->setDisplayMode(Mage_Catalog_Model_Category::DM_PRODUCT);
        $parentId = Mage_Catalog_Model_Category::TREE_ROOT_ID;
        $parentCategory = Mage::getModel('catalog/category')->load($parentId);
        $category->setPath($parentCategory->getPath());
        try{
            $category->save();
        }catch (Exception $e){

        }
    }

    public function pushProductToQueue($observer){
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getProduct();
        $realTime = $enable = Mage::getStoreConfig('citrus_sync/citrus_product/sync_mode', Mage::app()->getStore());
        if($realTime){
            if($product->hasDataChanges()){
                $this->pushItemToQueue($product,$product->getId());
            }
        }
        else{
            $helper = $this->getHelper();
            $body = $helper->getProductData($product);
            $response = $this->getHelper()->getRequestModel()->pushCatalogProductsRequest($body);
            $this->getHelper()->handleResponse($response);
        }
    }
    public function pushOrderToQueue($observer){
        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getOrder();
        $customer = $order->getCustomer();
        $realTimeOrder = $enable = Mage::getStoreConfig('citrus_sync/citrus_order/sync_mode', Mage::app()->getStore());
        if($realTimeOrder){
            $this->pushItemToQueue($order, $order->getIncrementId());
            $this->pushItemToQueue($customer, $customer->getId());
        }
        else{
            $body = $this->getHelper()->getOrderData($order);
            $response = $this->getHelper()->getRequestModel()->pushOrderRequest([$body]);
            $this->getHelper()->handleResponse($response, 'order', $order->getIncrementId());
        }
    }

    /**
     * @param $item
     * @param $entity_id
     */
    public function pushItemToQueue($item, $entity_id){
        /** @var Citrus_Integration_Model_Queue $queueModel */
        $queueModel = $this->getQueueModel();
        $queueCollection = $queueModel->getCollection()->addFieldToSelect('id')
            ->addFieldToFilter('type', ['eq'=> $item->getResourceName()])
            ->addFieldToFilter('entity_id', ['eq' => $entity_id])
            ->getFirstItem();
        if($queueCollection->getData()){
            $queueModel->load($queueCollection->getId());
            $queueModel->enqueue($entity_id, $item->getResourceName());
        }else {
            $queueModel->enqueue($entity_id, $item->getResourceName());
        }
    }
    protected function getConfigValue($type)
    {
        $path = 'citrus_sync/'. $type .'/frequency';

        return Mage::getStoreConfig($path, Mage::app()->getStore());
    }
    public function cronQueue(){
        $productCron = Mage::getStoreConfig('citrus_sync/citrus_product/sync_mode', Mage::app()->getStore());
        $orderCron = Mage::getStoreConfig('citrus_sync/citrus_order/sync_mode', Mage::app()->getStore());
        Mage::log('My log entry'.time());
        if($productCron){
            if ($time = $this->getConfigValue('citrus_product')) {
                if ($this->calculateTime($time)) {
                    $this->getHelper()->getSyncModel()->syncData('catalog/product');
                }
            }
        }
        if($orderCron){
            if ($time = $this->getConfigValue('citrus_order')) {
                if ($this->calculateTime($time)) {
                    $this->getHelper()->getSyncModel()->syncData('customer/customer');
                    $this->getHelper()->getSyncModel()->syncData('sales/order');
                }
            }
        }
    }
    /**
     * Calculate time
     *
     * @param $time
     * @return bool
     */
    protected function calculateTime($time)
    {
        $minute = date('i');
        $hour = date('h');
        /** change minute 0 to minute 60th */
        if ($minute == 0) {
            $minute = 60;
        }

        return ($minute % $time == 0) || ($time == 120 && $hour % 2 == 0);
    }
}