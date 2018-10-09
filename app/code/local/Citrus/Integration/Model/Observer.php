<?php
class Citrus_Integration_Model_Observer
{
    const DELIM = ";";
    const WEBSITE = "website:";
    const CATEGORY = "category:";
    const ATTRIBUTE = "attribute:";

    /**
     * @return false|Citrus_Integration_Model_Queue
     */
    protected function getQueueModel()
    {
        return Mage::getModel('citrusintegration/queue');
    }
    /**
     * @return false|Citrus_Integration_Model_Customer
     */
    protected function getCitrusCustomerModel()
    {
        return Mage::getModel('citrusintegration/customer');
    }
    /**
     * @return Mage_Core_Helper_Abstract|Citrus_Integration_Helper_Data
     */
    public static function getCitrusHelper()
    {
        return Mage::helper('citrusintegration/data');
    }
    public function sendContextAfterCms($observer)
    {
        $homeConfig = Mage::getStoreConfig('web/default/cms_home_page', Mage::app()->getStore());
        $bannerEnable = Mage::getStoreConfig('citrus_sync/citrus_banner/enable', Mage::app()->getStore());
        /** @var Mage_Cms_Model_Page $cms */
        $cms = $observer->getData('object');
        if($homeConfig == $cms->getIdentifier()){
            $websiteIds = array();
            $storeIds = $cms->getStoreId();
            foreach ($storeIds as $storeId){
                $websiteId = Mage::getModel('core/store')->load($storeId)->getWebsiteId();
                if (isset($websiteId)) {
                    $websiteIds[] = self::WEBSITE . $websiteId;
                }
            }

            if($websiteIds)
                $context['productFilters'] = implode(self::DELIM, array_unique($websiteIds));
            $context['pageType'] = 'Home';
            $banners = $this->getSlotIdByPageType($cms->getIdentifier(), Citrus_Integration_Helper_Data::CITRUS_PAGE_TYPE_CMS);
            if($banners) {
                $context['bannerSlotIds'] = $banners;
            }

            $context = $this->getCitrusHelper()->getContextData($context);
            $response = $this->getCitrusHelper()->getRequestModel()->requestingAnAd($context);

            $return = $this->getCitrusHelper()->handleAdsResponse($response, 'Home', false, $bannerEnable);
            try {
                Mage::unregister('categoryAdResponse');
                Mage::register('categoryAdResponse', $return);
            }catch (Exception $exception){
                $this->getCitrusHelper()->log('categoryAdResponse: '. Mage::registry('categoryAdResponse'), __FILE__, __LINE__);
            }

            $this->getCitrusHelper()->log('ads request homepage context '.' : '.json_encode($context), __FILE__, __LINE__);
            $this->getCitrusHelper()->log('ads request homepage '.' : '.$response['message'], __FILE__, __LINE__);
        }
    }
    public function sendContextAfterCategory($observer)
    {
        $moduleEnable = Mage::getStoreConfig('citrus/citrus_group/enable', Mage::app()->getStore());
        $bannerEnable = Mage::getStoreConfig('citrus_sync/citrus_banner/enable', Mage::app()->getStore());
        $adsEnable = Mage::getStoreConfig('citrus_sync/citrus_ads/enable', Mage::app()->getStore());
        $attributes = Mage::getStoreConfig('citrus_sync/product_attribute_filter/attribute', Mage::app()->getStore());
        $request = Mage::app()->getRequest()->getParams();
        if($attributes){
            $tmpAttributes = array();
            foreach (explode(',', $attributes) as $attribute){
                if(isset($request[$attribute])){
                    $tmpAttributes[] = self::ATTRIBUTE . $attribute.'_'.$request[$attribute];
                }
            }

            $attributes = implode(',', $tmpAttributes);
        }

        if(!$moduleEnable) $bannerEnable = $adsEnable = 0;
        if($bannerEnable || $adsEnable){
            /** @var Mage_Catalog_Model_Category $category */
            $category = $observer->getCategory();
            $websiteIds = self::WEBSITE . Mage::app()->getStore()->getWebsiteId();
            $productFilters = '';
            $parentCategories = $category->getParentCategories();
            if(is_array($parentCategories)){
                foreach ($parentCategories as $parentCategory){
                    $productFilters = $productFilters. self::DELIM . (self::CATEGORY . $parentCategory->getName());
                }

                $productFilters = trim($productFilters, self::DELIM);
            }

            if($category->getLevel() != '1'){
                $context = array(
                    'pageType' => 'Category',
                    'productFilters' => $websiteIds . self::DELIM . $productFilters . self::DELIM . $attributes
                );
                $banners = $this->getSlotIdByPageType($category->getEntityId(), Citrus_Integration_Helper_Data::CITRUS_PAGE_TYPE_CATEGORY);
                if($banners) {
                    $context['bannerSlotIds'] = $banners;
                }

                $context = $this->getCitrusHelper()->getContextData($context);
                $response = $this->getCitrusHelper()->getRequestModel()->requestingAnAd($context);

                $return = $this->getCitrusHelper()->handleAdsResponse($response, 'Category', $adsEnable, $bannerEnable);
                try {
                    Mage::unregister('categoryAdResponse');
                    Mage::register('categoryAdResponse', $return);
                }catch (Exception $exception){
                    $this->getCitrusHelper()->log('categoryAdResponse: '. Mage::registry('categoryAdResponse'), __FILE__, __LINE__);
                }

                $this->getCitrusHelper()->log('ads request category context -'.$productFilters.' : '.json_encode($context), __FILE__, __LINE__);
                $this->getCitrusHelper()->log('ads request category -'.$productFilters.' : '.$response['message'], __FILE__, __LINE__);
            }
        }
    }
    public function sendContextAfterSearch($observer)
    {
        $bannerEnable = Mage::getStoreConfig('citrus_sync/citrus_banner/enable', Mage::app()->getStore());
        $adsEnable = Mage::getStoreConfig('citrus_sync/citrus_ads/enable', Mage::app()->getStore());
        $moduleEnable = Mage::getStoreConfig('citrus/citrus_group/enable', Mage::app()->getStore());
        if(!$moduleEnable) $bannerEnable = $adsEnable = 0;
        if($bannerEnable || $adsEnable) {
            /** @var Mage_CatalogSearch_Model_Query $queryModel */
            $queryModel = $observer->getCatalogsearchQuery();
            $searchTerm = $queryModel->getQueryText();

            $context = array(
                'pageType' => 'Search',
                'searchTerm' => $searchTerm,
                'maxNumberOfAds' => Citrus_Integration_Helper_Data::MAX_NUMBER_OF_ADS
            );
            $banners = $this->getSlotIdByPageType('search', Citrus_Integration_Helper_Data::CITRUS_PAGE_TYPE_SEARCH);
            if ($banners) {
                $context['bannerSlotIds'] = $banners;
            }

            $context = $this->getCitrusHelper()->getContextData($context);
            $response = $this->getCitrusHelper()->getRequestModel()->requestingAnAd($context);
            $return = $this->getCitrusHelper()->handleAdsResponse($response, 'Search', $adsEnable, $bannerEnable);

            Mage::unregister('searchAdResponse');
            Mage::register('searchAdResponse', $return);
            $this->getCitrusHelper()->log('ads request search :'.$response['message'], __FILE__, __LINE__);
        }
    }
    public function getSlotIdByPageType($entity_id, $page_type)
    {
        /** @var Citrus_Integration_Model_Slotid $slotModel */
        $slotModel = Mage::getModel(Citrus_Integration_Model_Slotid::class);
        $banners = $slotModel->getSlotIdById($entity_id, $page_type);
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
    public function addDiscountToProduct($observer)
    {
        $moduleEnable = Mage::getStoreConfig('citrus/citrus_group/enable', Mage::app()->getStore());
        if($moduleEnable){
            /** @var Mage_Catalog_Model_Product $product */
            $product = $observer->getProduct();
            $this->applyDiscount($product);
            return $this;
        }
        else return $this;
    }
    public function applyDiscount($product)
    {
        $adModel = Mage::getModel('citrusintegration/ad');
        $discountModel = Mage::getModel('citrusintegration/discount');
        $host = $this->getCitrusHelper()->getHost();
        $datetime = new DateTime();
        $now = $datetime->format('Y-m-d\TH:i:s\Z');
        $adCollections = $adModel->getCollection()
            ->addFieldToSelect('*')
            ->addFieldToFilter('host', array('eq' => $host))
            ->addFieldToFilter('expiry', array('gteq' => $now));
        foreach ($adCollections as $adCollection){
            if($adCollection['gtin'] == $product->getSku()){
                $discount = $discountModel->load($adCollection->getDiscountId());
                if($product->getPrice() <= $discount->getMinPrice()){
                    continue;
                }else{
                    $newPrice = ($product->getPrice() - (float)$discount->getAmount()) <= $discount->getMinPrice() ? $discount->getMinPrice() : $product->getPrice() - (float)$discount->getAmount();
                    $product->setFinalPrice($newPrice);
                }
            }
        }
    }
    public function applyDiscountListProduct($observer)
    {
        $moduleEnable = Mage::getStoreConfig('citrus/citrus_group/enable', Mage::app()->getStore());
        if($moduleEnable) {
            $collections = $observer->getCollection();
            foreach ($collections as $collection) {
                $this->applyDiscount($collection);
            }

            return $this;
        }
        else return $this;
    }
    public function handleGetResponse($response, $type = null, $param = null)
    {
        $name = $this->getCitrusHelper()->getCitrusCatalogName();
        $host = $this->getCitrusHelper()->getHost();
        if ($response['success']) {
            if($type == 'catalog'){
                $data = json_decode($response['message'], true);
                if(is_array($data['catalogs']) && $data['catalogs']){
                    foreach ($data['catalogs'] as $catalog){
                        /** @var Citrus_Integration_Model_Catalog $model */
                        $model = Mage::getModel('citrusintegration/catalog');
                        $id = $model->getCatalogId();
                        if(!$id){
                            $catalogData = array(
                                'catalog_id' => $catalog['id'],
                                'teamId' => $catalog['teamId'],
                                'host' => $host,
                                'name' => $catalog['name']
                            );
                            $model->addData($catalogData);
                            try {
                                $model->save();
                                $this->handleGetResponse($response, Citrus_Integration_Model_Catalog::ENTITY, $param);
                            } catch (Exception $e) {
                                $this->getCitrusHelper()->log('Handle get catalog response error: '.$e->getMessage(), __FILE__, __LINE__);
                            }
                        }
                        else{
                            $this->pushCatalog($name, $id);
                            break;
                        }
                    }
                }
                else{
                    /** @var Citrus_Integration_Model_Catalog $model */
                    $model = Mage::getModel('citrusintegration/catalog');
                    $id = $model->getCatalogId();
                    if(!$id){
                        $this->pushCatalog($name);
                    }
                    else{
                        $this->pushCatalog($name, $id);
                    }
                }
            }
        }
        else {
            $data = json_decode($response['message'], true);
            $error = $data['message'] != '' ? $data['message'] : 'Something went wrong. Please try again in a few minutes';
            $error = Mage::helper('adminhtml')->__($error);
            Mage::throwException($error);
            $this->getCitrusHelper()->log('Handle banner response error: '.$response['message'], __FILE__, __LINE__);
        }
    }
    public function pushCatalog($name , $id = null)
    {
        $moduleEnable = Mage::getStoreConfig('citrus/citrus_group/enable', Mage::app()->getStore());
        if($moduleEnable) {
            $requestModel = $this->getCitrusHelper()->getRequestModel();
            $response = $requestModel->pushCatalogsRequest($name, $id);
            $this->getCitrusHelper()->handleResponse($response, Citrus_Integration_Model_Catalog::ENTITY, $name);
            $this->getCitrusHelper()->log('push catalog : ' . $response['message'], __FILE__, __LINE__);
        }
    }
    public function createCatalog($observer)
    {
        $enable = Mage::getStoreConfig('citrus/citrus_group/enable', Mage::app()->getStore());
        $catalogName = Mage::getStoreConfig('citrus/citrus_group/catalog_name', Mage::app()->getStore());
        if ($enable) {
            $responseModel = $this->getCitrusHelper()->getResponseModel();
            $response = $responseModel->getCatalogListResponse();
            $this->handleGetResponse($response, Citrus_Integration_Model_Catalog::ENTITY, $catalogName);
            $this->getCitrusHelper()->log('list catalog : '.$response['message'], __FILE__, __LINE__);
        }
    }
    public function createRootCategory($storeId, $name)
    {
        $moduleEnable = Mage::getStoreConfig('citrus/citrus_group/enable', Mage::app()->getStore());
        if($moduleEnable) {
            /** @var Mage_Catalog_Model_Category $category */
            $category = Mage::getModel('catalog/category');
            $category->setStoreId($storeId);
            $category->setName($name);
            $category->setIsActive(1);
            $category->setDisplayMode(Mage_Catalog_Model_Category::DM_PRODUCT);
            $parentId = Mage_Catalog_Model_Category::TREE_ROOT_ID;
            $parentCategory = Mage::getModel('catalog/category')->load($parentId);
            $category->setPath($parentCategory->getPath());
            try {
                $category->save();
                $this->getCitrusHelper()->log('create root category' . $category->getEntityId() . ':', __FILE__, __LINE__);
            } catch (Exception $e) {
                $this->getCitrusHelper()->log('create root category' . $e->getMessage(), __FILE__, __LINE__);
            }
        }
    }
    //delete
    /**
     * @param $observer
     */
    public function productDeleteEventAction($observer)
    {
        $moduleEnable = Mage::getStoreConfig('citrus/citrus_group/enable', Mage::app()->getStore());
        if ($moduleEnable) {
            /** @var Mage_Catalog_Model_Product $product */
            $product = $observer->getProduct();
            $response1 = $this->getCitrusHelper()->getRequestModel()->deleteCatalogProductRequest($product->getSku());
            $response2 = $this->getCitrusHelper()->getRequestModel()->deleteProductRequest($product->getSku());
            $this->getCitrusHelper()->log('delete catalog product- entityId: ' . $product->getEntityId() . 'sku: ' . $product->getSku() . ':' . $response1['message'], __FILE__, __LINE__);
            $this->getCitrusHelper()->log('delete product- entityId: ' . $product->getEntityId() . 'sku: ' . $product->getSku() . ':' . $response2['message'], __FILE__, __LINE__);
        }
    }
    public function pushProductToQueue($observer)
    {
        $moduleEnable = Mage::getStoreConfig('citrus/citrus_group/enable', Mage::app()->getStore());
        if($moduleEnable) {
            /** @var Mage_Catalog_Model_Product $product */
            $product = $observer->getProduct();

            $enableProduct = $product->getStatus();
            $queueModel = $this->getQueueModel();
            if ($enableProduct == 1) {
                $realTime = $enable = Mage::getStoreConfig('citrus_sync/citrus_product/sync_mode', Mage::app()->getStore());
                if ($realTime) {
                    if ($product->hasDataChanges()) {
                        $this->pushItemToQueue($queueModel, $product);
                        $this->getCitrusHelper()->log('push to queue product-' . $product->getEntityId() . ':', __FILE__, __LINE__);
                    }
                } else {
                    $helper = $this->getCitrusHelper();
                    $body = $helper->getCatalogProductData($product);
                    foreach ($body as $data) {
                        $response = $this->getCitrusHelper()->getRequestModel()->pushCatalogProductsRequest(array($data));
                        $this->getCitrusHelper()->log('push catalog product-' . $product->getEntityId() . ':' . $response['message'], __FILE__, __LINE__);
                    }

                    $this->pushCatalogProductAfter($product);
                }
            } else {
                $this->productDeleteEventAction($observer);
            }
        }
    }
    public function pushCatalogProductAfter($entity)
    {
        $helper = $this->getCitrusHelper();
        $body = $helper->getProductData($entity);
        $response = $this->getCitrusHelper()->getRequestModel()->pushProductsRequest(array($body));
        $this->getCitrusHelper()->log('push product-'.$entity->getEntityId().':'.$response['message'], __FILE__, __LINE__);
    }
    public function pushOrderToQueue($observer)
    {
        $moduleEnable = Mage::getStoreConfig('citrus/citrus_group/enable', Mage::app()->getStore());
        $queueModel = $this->getQueueModel();
        if($moduleEnable) {
            /** @var Mage_Sales_Model_Order $order */
            $order = $observer->getOrder();
//            $customer = $order->getCustomer();
            $realTimeOrder = $enable = Mage::getStoreConfig('citrus_sync/citrus_order/sync_mode', Mage::app()->getStore());
            if ($realTimeOrder) {
                $this->pushItemToQueue($queueModel, $order);
//                if ($order->getCustomerId()) {
//                    $this->pushItemToQueue($queueModel, $customer);
//                }
            } else {
                $body = $this->getCitrusHelper()->getOrderData($order);
                $this->getCitrusHelper()->log('push order request -' . $order->getEntityId() . ':' . json_encode($body), __FILE__, __LINE__);
                $response = $this->getCitrusHelper()->getRequestModel()->pushOrderRequest(array($body));
                $this->getCitrusHelper()->log('push order response -' . $order->getEntityId() . ':' . $response['message'], __FILE__, __LINE__);
                $this->getCitrusHelper()->handleResponse($response, Citrus_Integration_Model_Order::ENTITY, $order->getIncrementId());
            }
        }
    }
    public function pushCustomerToQueue($observer)
    {
        $moduleEnable = Mage::getStoreConfig('citrus/citrus_group/enable', Mage::app()->getStore());
        $queueModel = $this->getQueueModel();
        if($moduleEnable) {
            /** @var Mage_Customer_Model_Customer $customer */
            $customer = $observer->getCustomer();
            $citrusCustomerId = $this->getCitrusCustomerModel()->getCitrusIdByEntityId($customer->getEntityId());
            if (isset($citrusCustomerId)) {
                $this->getCitrusHelper()->log('Existing customer-' . $customer->getEntityId(), __FILE__, __LINE__);
                return;
            }

            $realTimeOrder = $enable = Mage::getStoreConfig('citrus_sync/citrus_order/sync_mode', Mage::app()->getStore());
            if ($realTimeOrder) {
                $this->pushItemToQueue($queueModel, $customer);
            } else {
                $body = $this->getCitrusHelper()->getCustomerData($customer);
                $response = $this->getCitrusHelper()->getRequestModel()->pushCustomerRequest(array($body));
                $this->getCitrusHelper()->log('push customer-' . $customer->getEntityId() . ':' . $response['message'], __FILE__, __LINE__);
                $this->getCitrusHelper()->handleResponse($response, Citrus_Integration_Model_Customer::ENTITY, $customer->getId());
            }
        }
    }


    public function customerDeleteEventAction($observer)
    {
        $moduleEnable = Mage::getStoreConfig('citrus/citrus_group/enable', Mage::app()->getStore());
        if($moduleEnable) {
            /** @var Mage_Catalog_Model_Product $customer */
            $customer = $observer->getCustomer();
            $citrus_id = $this->getCitrusHelper()->getCitrusIdById(Citrus_Integration_Model_Customer::ENTITY, $customer->getId());
            if ($citrus_id) {
                $response = $this->getCitrusHelper()->getRequestModel()->deleteCustomerRequest($citrus_id);
                $this->getCitrusHelper()->log('delete customer-' . $customer->getEntityId() . ':' . $response['message'], __FILE__, __LINE__);
            }
        }
    }

    /**
     * @param $queueModel
     * @param $item
     */
    public function pushItemToQueue($queueModel, $item)
    {
        /** @var Citrus_Integration_Model_Queue $queueModel */
//        $queueModel = $this->getQueueModel();
        $queueCollection = $queueModel->getCollection()->addFieldToSelect('id')
            ->addFieldToFilter('type', array('eq'=> $item->getResourceName()))
            ->addFieldToFilter('entity_id', array('in' => $item->getIncrementId()))
            ->getFirstItem();
        if($queueCollection->getData()){
            $queueModel->load($queueCollection->getId());
            $queueModel->enqueue($item->getIncrementId(), $item->getResourceName());
        }else {
            $queueModel->enqueue($item->getIncrementId(), $item->getResourceName());
        }
    }

    /**
     * @param $type
     * @return mixed
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function getConfigValue($type)
    {
        $path = 'citrus_sync/'. $type .'/frequency';

        return Mage::getStoreConfig($path, Mage::app()->getStore());
    }
    /**  */
    public function cronQueue()
    {
        $moduleEnable = Mage::getStoreConfig('citrus/citrus_group/enable', Mage::app()->getStore());
        if($moduleEnable) {
            $productCron = Mage::getStoreConfig('citrus_sync/citrus_product/sync_mode', Mage::app()->getStore());
            $orderCron = Mage::getStoreConfig('citrus_sync/citrus_order/sync_mode', Mage::app()->getStore());
            Mage::log('cron log start', null, 'citrus.log', true);
            if ($productCron) {
                if ($time = $this->getConfigValue('citrus_product')) {
                    if ($this->calculateTime($time)) {
                        $this->getCitrusHelper()->getSyncModel()->syncData('catalog/product');
                    }
                }
            }

            if ($orderCron) {
                if ($time = $this->getConfigValue('citrus_order')) {
                    if ($this->calculateTime($time)) {
                        $this->getCitrusHelper()->getSyncModel()->syncData('customer/customer');
                        $this->getCitrusHelper()->getSyncModel()->syncData('sales/order');
                    }
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
//        return true;
    }
}