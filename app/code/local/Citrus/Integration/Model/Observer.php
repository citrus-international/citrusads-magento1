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
    public static function getCitrusHelper(){
        return Mage::helper('citrusintegration/data');
    }

    public function sendContextAfterCategory($observer){
        $bannerEnable = Mage::getStoreConfig('citrus_sync/citrus_banner/enable', Mage::app()->getStore());
        $adsEnable = Mage::getStoreConfig('citrus_sync/citrus_ads/enable', Mage::app()->getStore());
        if($bannerEnable || $adsEnable){
            /** @var Mage_Catalog_Model_Category $category */
            $category = $observer->getCategory();
            $productFilters = '';
            $parentCategories = $category->getParentCategories();
            if(is_array($parentCategories)){
                foreach ($parentCategories as $parentCategory){
                    $productFilters = $productFilters.','.$parentCategory->getName();
                }
                $productFilters = trim($productFilters,',');
            }
            if($category->getLevel() != '1'){
                $context = [
                    'pageType' => 'Category',
                    'productFilters' => $productFilters
                ];
                $banners = $this->getSlotIdByPageType($category->getEntityId(), Citrus_Integration_Helper_Data::CITRUS_PAGE_TYPE_CATEGORY);
                if($banners) {
                    $context['bannerSlotIds'] = $banners;
                }
                $context = $this->getCitrusHelper()->getContextData($context);
                $response = $this->getCitrusHelper()->getRequestModel()->requestingAnAd($context);
                $return = $this->getCitrusHelper()->handleAdsResponse($response, 'Category', $adsEnable, $bannerEnable);
                Mage::register('categoryAdResponse', $return);
                Mage::log('My log entry'.time());
            }
        }
    }
    public function sendContextAfterSearch($observer){
        $bannerEnable = Mage::getStoreConfig('citrus/citrus_banner/enable', Mage::app()->getStore());
        $adsEnable = Mage::getStoreConfig('citrus/citrus_ads/enable', Mage::app()->getStore());
        if($bannerEnable || $adsEnable) {
            /** @var Mage_CatalogSearch_Model_Query $queryModel */
            $queryModel = $observer->getCatalogsearchQuery();
            $searchTerm = $queryModel->getQueryText();

            $context = [
                'pageType' => 'Search',
                'searchTerm' => $searchTerm,
                'maxNumberOfAds' => Citrus_Integration_Helper_Data::MAX_NUMBER_OF_ADS
            ];
            $banners = $this->getSlotIdByPageType('search', Citrus_Integration_Helper_Data::CITRUS_PAGE_TYPE_SEARCH);
            if ($banners) {
                $context['bannerSlotIds'] = $banners;
            }
            $context = $this->getCitrusHelper()->getContextData($context);
            $response = $this->getCitrusHelper()->getRequestModel()->requestingAnAd($context);
            $return = $this->getCitrusHelper()->handleAdsResponse($response, 'Search', $adsEnable, $bannerEnable);
//        test in search
//        $return['ads'] = [
//            148, 149, 150
//        ];
            Mage::register('searchAdResponse', $return);
            Mage::log('My log entry' . time());
        }
    }
    public function getSlotIdByPageType($entity_id, $page_type){
        /** @var Citrus_Integration_Model_Slotid $slotModel */
        $slotModel = Mage::getModel(Citrus_Integration_Model_Slotid::class);
        $banners = $slotModel->getSlotIdById($entity_id,$page_type);
        $result = null;
        if($banners) {
            $bannersMerge = '';
            foreach ($banners as $banner){
                $bannersMerge .= $banner['slot_id'].',';
            }
            $result = trim($bannersMerge, ',');
        }
        return $result;
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
        $host = $this->getCitrusHelper()->getHost();
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
        $name = $this->getCitrusHelper()->getCitrusCatalogName();
        $host = $this->getCitrusHelper()->getHost();
        if ($response['success']) {
            if($type == 'catalog'){
                $data = json_decode($response['message'], true);
                if(is_array($data['catalogs']) && $data['catalogs'] ){
                    foreach ($data['catalogs'] as $catalog){
                        /** @var Citrus_Integration_Model_Catalog $model */
                        $model = Mage::getModel('citrusintegration/catalog');
                        $id = $model->getCatalogId();
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
                                $this->handleGetResponse($response, Citrus_Integration_Model_Catalog::ENTITY, $param);
                            } catch (Exception $e) {

                            }
                        }
                        else{
                            $this->pushCatalog($name, $id);
                        }
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
    public function pushCatalog($name , $id = null){
        $requestModel = $this->getCitrusHelper()->getRequestModel();
        $response = $requestModel->pushCatalogsRequest($name, $id);
        $this->getCitrusHelper()->handleResponse($response, Citrus_Integration_Model_Catalog::ENTITY, $name);
        Mage::log('push catalog -'.'\n'.json_encode($response), null, 'citrus.log', true);
    }
    public function createCatalog($observer)
    {
        $enable = Mage::getStoreConfig('citrus/citrus_group/enable', Mage::app()->getStore());
        $catalogName = Mage::getStoreConfig('citrus/citrus_group/catalog_name', Mage::app()->getStore());
        if ($enable) {
            $responseModel = $this->getCitrusHelper()->getResponseModel();
            $response = $responseModel->getCatalogListResponse();
            $this->handleGetResponse($response, Citrus_Integration_Model_Catalog::ENTITY, $catalogName);
            Mage::log('create catalog -'.'\n'.json_encode($response), null, 'citrus.log', true);
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
            Mage::log('create root category-'.$category->getEntityId(), null, 'citrus.log', true);
        }catch (Exception $e){
            Mage::log('create root category-'.$e->getMessage(), null, 'citrus.log', true);
        }
    }
    //delete
    /**
     * @param $observer
     */
    public function productDeleteEventAction($observer){
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getProduct();
        $response = $this->getCitrusHelper()->getRequestModel()->deleteCatalogProductRequest($product->getEntityId());
        Mage::log('delete product-'.$product->getEntityId().'\n'.json_encode($response), null, 'citrus.log', true);
    }
    public function pushProductToQueue($observer){
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getProduct();
        $realTime = $enable = Mage::getStoreConfig('citrus_sync/citrus_product/sync_mode', Mage::app()->getStore());
        if($realTime){
            if($product->hasDataChanges()){
                $this->pushItemToQueue($product,$product->getId());
                Mage::log('push to queue product-'.$product->getEntityId(), null, 'citrus.log', true);
            }
        }
        else{
            $helper = $this->getCitrusHelper();
            $body = $helper->getCatalogProductData($product);
            $response = $this->getCitrusHelper()->getRequestModel()->pushCatalogProductsRequest($body);
            $this->getCitrusHelper()->handleResponse($response);
            Mage::log('push catalog product-'.$product->getEntityId().'\n'.json_encode($response), null, 'citrus.log', true);
            $this->pushCatalogProductAfter($product);

        }
    }
    public function pushCatalogProductAfter($entity){
        $helper = $this->getCitrusHelper();
        $body = $helper->getProductData($entity);
        $response = $this->getCitrusHelper()->getRequestModel()->pushProductsRequest($body);
        $this->getCitrusHelper()->handleResponse($response);
        Mage::log('push product-'.$entity->getEntityId().json_encode($response), null, 'citrus.log', true);
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
            $body = $this->getCitrusHelper()->getOrderData($order);
            $response = $this->getCitrusHelper()->getRequestModel()->pushOrderRequest([$body]);
            $this->getCitrusHelper()->handleResponse($response, Citrus_Integration_Model_Order::ENTITY, $order->getIncrementId());
        }
    }
    public function pushCustomerToQueue($observer){
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = $observer->getCustomer();
        $realTimeOrder = $enable = Mage::getStoreConfig('citrus_sync/citrus_order/sync_mode', Mage::app()->getStore());
        if($realTimeOrder){
            $this->pushItemToQueue($customer, $customer->getId());
        }
        else{
            $body = $this->getCitrusHelper()->getCustomerData($customer);
            $response = $this->getCitrusHelper()->getRequestModel()->pushCustomerRequest([$body]);
//            Mage::log('push customer -'.$customer->getEntityId().'\n'.json_encode($response), null, 'citrus.log', true);
            $this->getCitrusHelper()->handleResponse($response, Citrus_Integration_Model_Customer::ENTITY, $customer->getId());
        }
    }
    public function customerDeleteEventAction($observer){
        /** @var Mage_Catalog_Model_Product $customer */
        $customer = $observer->getCustomer();
        $citrus_id = $this->getCitrusHelper()->getCitrusIdById(Citrus_Integration_Model_Customer::ENTITY, $customer->getId());
        if($citrus_id){
            $response = $this->getCitrusHelper()->getRequestModel()->deleteCustomerRequest($citrus_id);
            Mage::log('delete customer-'.$customer->getEntityId().json_encode($response), null, 'citrus.log', true);
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
            if ($time = $this->getConfigValue('citrus_sync/citrus_product/frequency')) {
                if ($this->calculateTime($time)) {
                    $this->getCitrusHelper()->getSyncModel()->syncData('catalog/product');
                }
            }
        }
        if($orderCron){
            if ($time = $this->getConfigValue('citrus_sync/citrus_order/frequency')) {
                if ($this->calculateTime($time)) {
                    $this->getCitrusHelper()->getSyncModel()->syncData('customer/customer');
                    $this->getCitrusHelper()->getSyncModel()->syncData('sales/order');
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