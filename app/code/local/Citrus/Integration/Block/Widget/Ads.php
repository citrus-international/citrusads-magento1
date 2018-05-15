<?php
class Citrus_Integration_Block_Widget_Ads extends Mage_Catalog_Block_Product_New implements Mage_Widget_Block_Interface
{
    protected $_defaultColumnCount = 5;

    protected $_defaultMaxNumberAds = 5;

    protected function _construct()
    {
        parent::_construct();
        $this->addPriceBlockType('bundle', 'bundle/catalog_product_price', 'bundle/catalog/product/price.phtml');
    }
    public function getMaxNumberAds(){
        return (int)$this->getData('max_number_ads') > 0 ? (int)$this->getData('max_number_ads') : $this->_defaultMaxNumberAds;
    }
    public function getColumnCount(){
        return (int)$this->getData('column_count') > 0 ? (int)$this->getData('column_count') : $this->_defaultColumnCount;
    }
    public function getPageType(){
        return $this->getData('page_type');
    }
    public function _getProductCollection(){
        $limit = $this->getMaxNumberAds();
        $pageType = $this->getPageType();
        $adCollections = Mage::getResourceModel('citrusintegration/ad')->getAds($limit, $pageType);
        /** @var $collection Mage_Catalog_Model_Resource_Product_Collection */

        $productIds = [];
        foreach ($adCollections as $adCollection){
            $productIds[] = $adCollection['gtin'];
        }
        $collection = Mage::getResourceModel('catalog/product_collection');
        $collection = $this->_addProductAttributesAndPrices($collection)
            ->addAttributeToFilter('entity_id', array('in' => $productIds))
            ->setPageSize($limit);
        return $collection;
    }
    /**
     * @return Citrus_Integration_Helper_Data
     */
    public function getCitrusHelper()
    {
        return Mage::helper('citrusintegration');
    }
}