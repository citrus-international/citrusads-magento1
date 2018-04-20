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

    public function createCatalog($observer)
    {
        $enable = Mage::getStoreConfig('citrus/citrus_group/enable', Mage::app()->getStore());
        $catalogName = Mage::getStoreConfig('citrus/citrus_group/catalog_name', Mage::app()->getStore());
        if ($enable) {
            $api = $this->getHelper()->getRequestModel();
            /** @var Citrus_Integration_Model_Catalog $model */
            $model = Mage::getModel('citrusintegration/catalog');
            if($model->getCatalogId() == false) {
                $response = $api->pushCatalogsRequest($catalogName);
                $this->getHelper()->handleResponse($response, 'catalog', $catalogName);
            }
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
                    /** @var Mage_Catalog_Model_Product $collection */
                    foreach ($collections as $collection) {
                        $tags = $this->getHelper()->getProductTags($collection->getId());
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
                    $response = $this->getHelper()->getRequestModel()->pushCatalogProductsRequest($body);
                    $this->getHelper()->handleResponse($response);
                }
            }
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