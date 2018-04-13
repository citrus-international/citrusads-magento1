<?php
class Citrus_Integration_Model_Observer
{

    /**
     * @return false|Citrus_Integration_Model_Service_Request
     */
    protected function getRequestModel(){
        return Mage::getModel('citrusintegration/service_request');
    }
    /**
     * @return false|Citrus_Integration_Helper_Data
     */
    protected function getHelper(){
        return Mage::helper('citrusintegration/data');
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
    public function createCatalog($observer)
    {
        $enable = Mage::getStoreConfig('citrus/citrus_group/enable', Mage::app()->getStore());
        $catalogName = Mage::getStoreConfig('citrus/citrus_group/catalog_name', Mage::app()->getStore());
        if ($enable) {
            $api = $this->getRequestModel();
            /** @var Citrus_Integration_Model_Catalog $model */
            $model = Mage::getModel('citrusintegration/catalog');
            //create one root category
            if($model->getCatalogId() == false) {
                $response = $api->pushCatalogsRequest($catalogName);
                $this->handleResponse($response, 'create-catalog', $catalogName);
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
                        $body = $data;
                    }
                    $response = $this->getRequestModel()->pushCatalogProductsRequest($body, $catalogId);
                    $this->handleResponse($response);
                }
            }
        }
    }
    protected function handleResponse($response,$type = null, $name = null){
        if ($response['success'] && $type == 'create-catalog') {
            $data = json_decode($response['message'], true);
            $catalogData['catalog_id'] = $data['catalogs'][0]['id'];
            $catalogData['name'] = $data['catalogs'][0]['name'];
            $model = Mage::getModel('citrusintegration/catalog')->setData($catalogData);
            try {
                $model->save();
                $this->createRootCategory(Mage::app()->getStore(), $name);
            } catch (Exception $e) {

            }
        }
        elseif($response['success'] == false) {
            $data = json_decode($response['message'], true);
            $error = $data['message'] != '' ? $data['message'] : 'Something went wrong. Please try again in a few minutes';
            $error = Mage::helper('adminhtml')->__($error);
            Mage::throwException($error);
        }
    }
    protected function getProductTags($id){
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
}