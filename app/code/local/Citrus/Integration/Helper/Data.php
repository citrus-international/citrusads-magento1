<?php
/**
 * Citrus_Integration extension
 * @package Citrus_Integration
 * @copyright Copyright (c) 2017 Assembly Payments (https://assemblypayments.com/)
 */

/**
 * Class Citrus_Integration_Helper_Data
 */
class Citrus_Integration_Helper_Data extends Mage_Core_Helper_Data
{
    const CITRUS_STAGING_SERVER = "https://staging-integration.citrusad.com/v1/";
    const CITRUS_AU_SERVER = "https://au-integration.citrusad.com/v1/";
    const CITRUS_US_SERVER = "https://us-integration.citrusad.com/v1/";
    const MAX_NUMBER_OF_ADS = 3;
    const CITRUS_PAGE_TYPE_SEARCH = 3;
    const CITRUS_PAGE_TYPE_ALL = 0;
    const CITRUS_PAGE_TYPE_CATEGORY = 1;
    const CITRUS_PAGE_TYPE_CMS = 2;

    public function getTeamId(){
        return Mage::getStoreConfig('citrus/citrus_group/team_id', Mage::app()->getStore());
    }

    /** @return false|Citrus_Integration_Model_Sync */
    public function getSyncModel(){
        return Mage::getModel('citrusintegration/sync');
    }

    public function getApiKey(){
        return Mage::getStoreConfig('citrus/citrus_group/api_key', Mage::app()->getStore());
    }
    /**
     * @return false|Citrus_Integration_Model_Banner
     */
    public function getBannerModel(){
        return Mage::getModel('citrusintegration/banner');
    }

    /**
     * @return false|Citrus_Integration_Model_Discount
     */
    protected function getDiscountModel(){
        return Mage::getModel('citrusintegration/discount');
    }
    /**
     * @return false|Citrus_Integration_Model_Ad
     */
    protected function getAdModel(){
        return Mage::getModel('citrusintegration/ad');
    }
    public function getHost(){
        $host = Mage::getStoreConfig('citrus/citrus_group/host', Mage::app()->getStore());
        switch ($host){
            case 1:
                return self::CITRUS_AU_SERVER;
                break;
            case 2:
                return self::CITRUS_US_SERVER;
                break;
            default:
                return self::CITRUS_STAGING_SERVER;
                break;
        }
    }
    /**
     * @return false|string
     */
    public function getCitrusCatalogId(){
        /** @var Citrus_Integration_Model_Catalog $model */
        $model = Mage::getModel('citrusintegration/catalog');
//        $name = $this->getCitrusCatalogName();
        return $model->getCatalogId();
    }
    public function getCitrusCatalogName(){
        return Mage::getStoreConfig('citrus/citrus_group/catalog_name', Mage::app()->getStore());
    }
    /**
     * @return false|Citrus_Integration_Model_Service_Request
     */
    public function getRequestModel(){
        return Mage::getModel('citrusintegration/service_request');
    }
    /**
     * @return false|Citrus_Integration_Model_Service_Response
     */
    public function getResponseModel(){
        return Mage::getModel('citrusintegration/service_response');
    }
    public function log($messages, $file = '', $line = 0){
            Mage::log($messages . ' on '.$file .':'.$line, null, 'citrus.log', true);
    }
    /**
     * @param $entity Mage_Sales_Model_Order
     * @return mixed
     */
    public function getOrderData($entity){
        $teamId = $this->getTeamId();
        $data['teamId'] = $teamId;
        if($entity->getCustomerId())
            $data['customerId'] = $entity->getCustomerId();
        $datetime = DateTime::createFromFormat("Y-m-d H:i:s", $entity->getCreatedAt());
        $data['orderDate'] = $datetime->format(\DateTime::RFC3339);
        $orderItems = $entity->getAllItems();
        foreach ($orderItems as $orderItem){
            $data['orderItems'][] = $this->getOrderItemData($orderItem);
        }
        return $data;
    }
    public function getContextData($context = null){
        $data = [
            'catalogId' => $this->getCitrusCatalogId(),
            'pageType' => isset($context['pageType']) ? $context['pageType'] : 'Home',
            'maxNumberOfAds' => self::MAX_NUMBER_OF_ADS
        ];
        if(isset($context['searchTerm']))
            $data['searchTerm'] = $context['searchTerm'];
        if(isset($context['bannerSlotIds'])){
            $arrays = explode(',',$context['bannerSlotIds']);
            $data['bannerSlotIds'] = $arrays;
        }
        if(isset($context['productFilters'])){
            $arrays = explode(',',$context['productFilters']);
//            foreach ($arrays as $array){
                $data['productFilters'] = [$arrays];
//            }
        }
        if(isset($context['customerId']))
            $data['customerId'] = $context['customerId'];
        return $data;
    }

    public function handleAdsResponse($response, $pageType = null, $adsEnable = true, $bannerEnable = true){
        if($response['success']){
            $data = json_decode($response['message'], true);
            $adModel = $this->getAdModel();
            $bannerModel = $this->getBannerModel();
            $discountModel = $this->getDiscountModel();
            $host = $this->getHost();
            $adsRegistry = [];
            $bannerRegistry = [];
            if($data['ads'] && $adsEnable){
                foreach ($data['ads'] as $ad){
                    $id = $adModel->getIdByCitrusId($ad['id']);
                    if($id){
                        $adModel->load($id);
                        $discountModel->load($adModel->getDiscountId());
                        $adData = [
                            'gtin' => $ad['gtin'],
                            'expiry' => $ad['expiry']
                        ];
                        $discountData = [
                            'amount' => $ad['discount']['amount'],
                            'minPrice' => $ad['discount']['minPrice'],
                            'maxPerCustomer' => $ad['discount']['maxPerCustomer'],
                        ];
                        $adModel->addData($adData);
                        $discountModel->addData($discountData);

                        try{
                            $discountModel->save();
                            $adModel->save();
                            $adsRegistry[] = $adModel->getId();
                        }catch (Exception $e){
                            $this->log('Get ads response error: '.$e->getMessage(), __FILE__, __LINE__);
                            return false;
                        }
                    }
                    else{
                        $discountModel->unsetData();
                        $discountData = [
                            'amount' => $ad['discount']['amount'],
                            'minPrice' => $ad['discount']['minPrice'],
                            'maxPerCustomer' => $ad['discount']['maxPerCustomer'],
                        ];
                        $discountModel->addData($discountData);
                        try{
                            $discountModel->save();
                        }catch (Exception $e){
                            $this->log('Get ads response error: '.$e->getMessage(), __FILE__, __LINE__);
                            return false;
                        }
                        $adModel->unsetData();
                        $adData = [
                            'citrus_id' => $ad['id'],
                            'discount_id' => $discountModel->getId(),
                            'pageType' => $pageType,
                            'gtin' => $ad['gtin'],
                            'expiry' => $ad['expiry'],
                            'host' => $host
                        ];
                        $adModel->addData($adData);
                        try{
                            $adModel->save();
                            $adsRegistry[] = $adModel->getId();
                        }catch (Exception $e){
                            $this->log('Get ads response error: '.$e->getMessage(), __FILE__, __LINE__);
                            return false;
                        }
                    }
                }
            }
            if($data['banners'] && $bannerEnable){
                foreach ($data['banners'] as $banner){
                    $id = $bannerModel->getIdByCitrusId($banner['id']);
                    if($id){
                        $bannerModel->load($id);
                        $bannerData = [
                            'slotId' => $banner['slotId'],
                            'imageUrl' => $banner['imageUrl'],
                            'altText' => $banner['altText'],
                            'linkUrl' => $banner['linkUrl'],
                            'expiry' => $banner['expiry'],
                            'pageType' => $pageType
                        ];
                        $bannerModel->addData($bannerData);
                        try{
                            $bannerModel->save();
                            $bannerRegistry[] = $bannerModel->getId();
                        }catch (Exception $e){
                            $this->log('Get ads response error: '.$e->getMessage(), __FILE__, __LINE__);
                            return false;
                        }
                    }
                    else{
                        $bannerModel->unsetData();
                        $bannerData = [
                            'citrus_id' => $banner['id'],
                            'slotId' => $banner['slotId'],
                            'imageUrl' => $banner['imageUrl'],
                            'altText' => $banner['altText'],
                            'linkUrl' => $banner['linkUrl'],
                            'expiry' => $banner['expiry'],
                            'host' => $host,
                            'pageType' => $pageType
                        ];
                        $bannerModel->addData($bannerData);
                        try{
                            $bannerModel->save();
                            $bannerRegistry[] = $bannerModel->getId();
                        }catch (Exception $e){
                            $this->log('Get ads response error: '.$e->getMessage(), __FILE__, __LINE__);
                            return false;
                        }
                    }
                }
            }
            return ['ads'=>$adsRegistry,'banners'=>$bannerRegistry];
        }
        else{
            $this->log('Get ads response error: '.$response['message'], __FILE__, __LINE__);
            return false;
        }
    }
    public function handleBanner($banners, $adId){
        /** @var Citrus_Integration_Model_Banner $model */
        $model = Mage::getModel('citrusintegration/banner');
        $host = $this->getHelper()->getHost();
        if(!is_array($banners)){
            $ids = $model->getIdByAdId(1);
            if($ids){
                foreach ($ids as $id){
                    $model->load($id['id']);
                    try{
                        $model->delete();
                    }catch (Exception $e) {
                        $this->log('Handle banner response error: '.$e->getMessage(), __FILE__, __LINE__);
                    }
                }
            }
        }
        else {
            foreach ($banners as $banner){
                $ids = $model->getIdByAdId($adId);
                if($ids){
                    foreach ($ids as $id){
                        $model->load($id['id']);
                        try{
                            $model->delete();
                        }catch (Exception $e) {
                            $this->log('Get ads response error: '.$e->getMessage(), __FILE__, __LINE__);
                        }
                    }
                    $bannerData = [
                        "id" => $banner['id'],
                        "slotId" => $banner['slotId'],
                        "imageUrl" => $banner['imageUrl'],
                        "linkUrl" => $banner['linkUrl'],
                        "altText" => $banner['altText'],
                        "expiry" => $banner['expiry'],
                        "ad_id" => $adId,
                        "host" => $host
                    ];
                    $model->addData($bannerData);
                    try{
                        $model->save();
                    }catch (Exception $e){
                        $this->log('Get ads response error: '.$e->getMessage(), __FILE__, __LINE__);
                    }
                }
                else{
                    $bannerData = [
                        "id" => $banner['id'],
                        "slotId" => $banner['slotId'],
                        "imageUrl" => $banner['imageUrl'],
                        "linkUrl" => $banner['linkUrl'],
                        "altText" => $banner['altText'],
                        "expiry" => $banner['expiry'],
                        "ad_id" => $adId,
                        "host" => $host
                    ];
                    $model->addData($bannerData);
                    try{
                        $model->save();
                    }catch (Exception $e){
                        $this->log('Get ads response error: '.$e->getMessage(), __FILE__, __LINE__);
                    }
                }
            }
        }
    }

    public function handleResponse($response,$type = null, $name = null){
        if ($response['success']) {
            if($type == 'catalog'){
                $host = $this->getHost();
                $teamId = $this->getTeamId();
                $data = json_decode($response['message'], true);
                foreach ($data['catalogs'] as $catalog){
                    $catalogData['catalog_id'] = $catalog['id'];
                    $catalogData['name'] = $catalog['name'];
                    $catalogData['host'] = $host;
                    $catalogData['teamId'] = $teamId;
                    /** @var Citrus_Integration_Model_Catalog $model */
                    $model = Mage::getModel('citrusintegration/catalog')->setData($catalogData);
                    $modelId = $model->getIdByCitrusId($catalog['id']);
                    if($modelId)
                        $model->load($modelId);
                    try {
                        $model->save();
                    } catch (Exception $e) {
                        $this->log('Handle catalog response error: '.$e->getMessage(), __FILE__, __LINE__);
                    }
                }
            }
            elseif($type == 'customer'){
                $data = json_decode($response['message'], true);
                foreach ($data['customers'] as $key => $customer){
                    $customerData['citrus_id'] = $customer['id'];
                    $customerData['entity_id'] = isset($name[$key]) ? $name[$key] : $name;
                    $model = Mage::getModel('citrusintegration/customer')->setData($customerData);
                    try {
                        $model->save();
                    } catch (Exception $e) {
                        $this->log('Handle customer response error: '.$e->getMessage(), __FILE__, __LINE__);
                    }
                }

            }
            elseif($type == 'order'){
                $data = json_decode($response['message'], true);
                foreach ($data['orders'] as $key => $order){
                    $orderData['citrus_id'] = $order['id'];
                    $orderData['entity_id'] = isset($name[$key]) ? $name[$key] : $name;
                    $model = Mage::getModel('citrusintegration/order')->setData($orderData);
                    try {
                        $model->save();
                    } catch (Exception $e) {
                        $this->log('Handle order response error: '.$e->getMessage(), __FILE__, __LINE__);
                    }
                }
            }
        }
        else {
            $this->log('Handle response to citrus table:'.$response['message'], __FILE__, __LINE__);
        }
    }
    /**
     * @param $item Mage_Sales_Model_Order_Item
     * @return array
     */
    public function getOrderItemData($item){
        $data['gtin'] = $item->getProductId();
        $data['quantity'] = (int)$item->getQtyOrdered();
        $data['regularUnitPrice'] = (int)$item->getBasePrice();
        $data['totalOrderItemPriceAfterDiscounts'] = (float)$item->getRowTotal();
        $info_buyRequest = $item->getProductOptionByCode('info_buyRequest');
        if(isset($info_buyRequest['citrus_ad_id'])){
            $data['adId'] = $info_buyRequest['citrus_ad_id'];
            $adModel = $this->getAdModel();
            $discountModel = $this->getDiscountModel();
            $discount = $discountModel->load($adModel->load($adModel->getIdByCitrusId($info_buyRequest['citrus_ad_id']))->getDiscountId())->getAmount();
            if(isset($discount)){
                $data['citrusDiscountAmount'] = (int)$discount;
            }
        }
        return $data;
    }
    /**
     * @param $entity Mage_Customer_Model_Customer
     * @return mixed
     */
    public function getCustomerData($entity){
        $teamId = $this->getTeamId();
        $data['teamId'] = $teamId;
        $citrus_id = $this->getCitrusIdById(Citrus_Integration_Model_Customer::ENTITY, $entity->getId());
        if($citrus_id)
            $data['id'] = $citrus_id;
        $gender = $entity->getGender();
        if($gender == 1)
            $gender = 'Male';
        elseif($gender == 2)
            $gender = 'Female';
        else
            $gender = 'Other';
        $data['gender'] = $gender;
        $dob = $entity->getDob();
        if($dob){
            $datetime = new DateTime($dob);
            $year = $datetime->format('Y');
            $data['yearOfBirth'] = (int)$year;
        }
        $address = $entity->getDefaultBillingAddress();
        $data['postcode'] = isset($address)&& $address ? $address->getPostcode() : '';
        return $data;
    }
    public function getCitrusIdById($type, $id){
        $model = Mage::getModel('citrusintegration/'.$type);
        $citrus_id = $model->getCitrusIdById($id);
        return $citrus_id;
    }
    /**
     * @param $entity Mage_Catalog_Model_Product
     * @return mixed
     */
    public function getProductData($entity){
        $data['gtin'] = $entity->getId();
        $data['name'] = $entity->getName();
        if ($entity->getImage() != 'no_selection')
            $data['images'] = [Mage::getModel('catalog/product_media_config')->getMediaUrl($entity->getImage())];
        if($entity->getSize())
            $data['size'] = $entity->getSize();
        $categoryIds = $entity->getResource()->getCategoryIds($entity);
        $catModel = Mage::getModel('catalog/category')->setStoreId(Mage::app()->getStore()->getId());
        if (is_array($categoryIds)) {
            foreach ($categoryIds as $categoryId) {
                $category = $catModel->load($categoryId);
                $data['categoryHierarchies'][] = $this->getCategoryHierarchies($category);
            }
        }
        return $data;
    }
    /**
     * @param $entity Mage_Catalog_Model_Product
     * @return array
     */
    public function getCatalogProductData($entity){
        $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($entity);
        $catalogId = $this->getCitrusCatalogId();
        $teamId = $this->getTeamId();
        $tags = $this->getProductTags($entity->getId());
        $categoryIds = $entity->getResource()->getCategoryIds($entity);
        $catModel = Mage::getModel('catalog/category')->setStoreId(Mage::app()->getStore()->getId());
        if (is_array($categoryIds) && $categoryIds){
            foreach ($categoryIds as $key => $categoryId) {
                /** @var Mage_Catalog_Model_Category $category */
                $category = $catModel->load($categoryId);
                $data[$key]['catalogId'] = $catalogId;
                $data[$key]['teamId'] = $teamId;
                $data[$key]['gtin'] = $entity->getId();
                $data[$key]['inventory'] = (int)$stock->getQty();
                $data[$key]['price'] = (int)$entity->getPrice();
                $data[$key]['categoryHierarchy'] = $this->getCategoryHierarchies($category);
                $data[$key]['tags'] = $tags;
                $data[$key]['filters'] = $this->getProductFilter($entity);
                $data[$key]['profit'] = null;
            }
        }

        else{
            $data[0]['catalogId'] = $catalogId;
            $data[0]['teamId'] = $teamId;
            $data[0]['gtin'] = $entity->getId();
            $data[0]['inventory'] = (int)$stock->getQty();
            $data[0]['price'] = (int)$entity->getPrice();
            $data[0]['tags'] = $tags;
            $data[0]['filters'] = $this->getProductFilter($entity);
            $data[0]['profit'] = null;
        }

        return $data;
    }
//    public function get
    /**
     * @param Mage_Catalog_Model_Category $category
     * @param array $array
     * @return mixed
     */
    public function getCategoryHierarchies($category, $array = []){
        $parents = explode('/', $category->getpath() );
        foreach ($parents as $parentId){
            $parent = Mage::getModel(Mage_Catalog_Model_Category::class)->load($parentId);
            if($parent->getLevel() != 0 && $parent->getLevel() != 1){
                $array[] = $parent->getName();
            }
        }
        return $array;
    }
    /**
     * @param $product Mage_Catalog_Model_Product
     * @return array
     */
    public function getProductFilter($product){
        $categoryIds = $product->getResource()->getCategoryIds($product);
        $websites = $product->getWebsiteIds();
        $cats = [];
        if(is_array($categoryIds)) {
            foreach ($categoryIds as $category_id) {
                /** @var Mage_Catalog_Model_Category $category */
                $category = Mage::getModel(Mage_Catalog_Model_Category::class)->load($category_id);
                $cats[] = $this->getCategoryHierarchies($category);
            }
        }
        foreach ($cats as $cat){
            $result = array_merge(isset($result) ? $result : [],$cat);
        }
        return array_values(array_unique(array_merge($websites,isset($result) ? $result : [])));
    }
    public function getProductTags($id){
        $results = [];
        $model=Mage::getModel('tag/tag');
        $tags= $model->getResourceCollection()
            ->addPopularity()
            ->addStatusFilter($model->getApprovedStatus())
            ->addProductFilter($id)
            ->addStoreFilter(Mage::app()->getStore()->getId())
            ->setActiveFilter();
        foreach ($tags as $tag){
            $results[] = $tag->getName();
        }
        return $results;
    }
//    public function handleData($itemId, $type){
//        $itemModel = Mage::getModel($type);
//
//        switch ($type){
//            case 'catalog/product':
//                /** @var  $itemModel Mage_Catalog_Model_Product */
//                $entity = $itemModel->load($itemId);
//                $body = $this->getCatalogProductData($entity);
//                $response = $this->getRequestModel()->pushCatalogProductsRequest($body);
//                $body = $this->getProductData($entity);
//                $response = $this->getRequestModel()->pushProductsRequest($body);
//                break;
//            case 'customer/customer':
//                /** @var  $itemModel Mage_Customer_Model_Customer */
//                $entity = $itemModel->load($itemId);
//                $body = $this->getCustomerData($entity);
//                $response = $this->getRequestModel()->pushCustomerRequest([$body]);
//                break;
//            case 'sales/order':
//                /** @var  $itemModel Mage_Sales_Model_Order */
//                $entity = $itemModel->loadByIncrementId($itemId);
//                $body = $this->getOrderData($entity);
//                $response = $this->getRequestModel()->pushOrderRequest([$body]);
//                break;
//        }
//        $this->handleResponse($response);
//    }
    public function handlePostResponse($response){
        if($response['success']){
            $data = json_decode($response['message'], true);
            $adModel = $this->getAdModel();
            $discountModel = $this->getDiscountModel();
            $host = $this->getHost();
            if($data['ads']){
                foreach ($data['ads'] as $ad){
                    $id = $adModel->getIdByCitrusId($ad['id']);
                    if($id){
                        $adModel->load($id);
                        $discountModel->load($adModel->getDiscountId());
                        $adData = [
                            'gtin' => $ad['gtin'],
                            'expiry' => $ad['expiry']
                        ];
                        $discountData = [
                            'amount' => $ad['discount']['amount'],
                            'minPrice' => $ad['discount']['minPrice'],
                            'maxPerCustomer' => $ad['discount']['maxPerCustomer'],
                        ];
                        $adModel->addData($adData);
                        $discountModel->addData($discountData);

                        try{
                            $discountModel->save();
                            $adModel->save();
                        }catch (Exception $e){
                            $this->log('Handle ads response error: '.$e->getMessage(), __FILE__, __LINE__);
                            return false;
                        }
                    }
                    else{
                        $discountModel->unsetData();
                        $discountData = [
                            'amount' => $ad['discount']['amount'],
                            'minPrice' => $ad['discount']['minPrice'],
                            'maxPerCustomer' => $ad['discount']['maxPerCustomer'],
                        ];
                        $discountModel->addData($discountData);
                        try{
                            $discountModel->save();
                        }catch (Exception $e){
                            $this->log('Handle ads response error: '.$e->getMessage(), __FILE__, __LINE__);
                            return false;
                        }
                        $adModel->unsetData();
                        $adData = [
                            'citrus_id' => $ad['id'],
                            'discount_id' => $discountModel->getId(),
                            'gtin' => $ad['gtin'],
                            'expiry' => $ad['expiry'],
                            'host' => $host
                        ];
                        $adModel->addData($adData);
                        try{
                            $adModel->save();
                        }catch (Exception $e){
                            $this->log('Handle ads response error: '.$e->getMessage(), __FILE__, __LINE__);
                            return false;
                        }
                    }
                }
            }
            return true;
        }
        else{
            $this->log('Error when get response: '.$response['message'], __FILE__, __LINE__);
            return false;
        }
    }
    public function getAllCategoriesArray($optionList = false)
    {
        $categories = array();
        $allCategoriesCollection = Mage::getModel('catalog/category')
            ->getCollection()
            ->addAttributeToSelect('name')
            ->addFieldToFilter('level', array('gt'=>'0'));
        $allCategoriesArray = $allCategoriesCollection->load()->toArray();
        $categoriesArray = $allCategoriesCollection
            ->addAttributeToSelect('level')
            ->addAttributeToSort('path', 'asc')
            ->addFieldToFilter('is_active', array('eq'=>'1'))
            ->addFieldToFilter('level', array('gt'=>'1'))
            ->load()
            ->toArray();
        foreach ($categoriesArray as $categoryId => $category)
        {
            if (!isset($category['name'])) {
                continue;
            }
            $categoryIds = explode('/', $category['path']);
            $nameParts = array();
            foreach($categoryIds as $catId) {
                if($catId == 1) {
                    continue;
                }
                $nameParts[] = $allCategoriesArray[$catId]['name'];
            }
            $categories[$categoryId] = array(
                'value' => $categoryId,
                'label' => implode(' / ', $nameParts)
            );
        }

        return $categories;
    }
}
