<?php

class Citrus_Integration_Block_Product_List extends Mage_Catalog_Block_Product_List
{
    /**
     * Default toolbar block name
     *
     * @var string
     */
    protected $_defaultToolbarBlock = 'catalog/product_list_toolbar';

    /**
     * Product Collection
     *
     * @var Mage_Eav_Model_Entity_Collection_Abstract
     */
    protected $_productCollection;

    public function getAdResponse($responses,$collections){
        $adProductIds = [];
        foreach ($responses as $response){
            $adModel = Mage::getModel(Citrus_Integration_Model_Ad::class)->load($response);
            $sku = $adModel->getGtin();
            /** @var Mage_Catalog_Model_Product $productModel */
            $productModel = Mage::getModel(Mage_Catalog_Model_Product::class);
            $id = $productModel->getIdBySku($sku);
            if($id) {
                $product = $productModel->load($id);
                $citrus_ad_id = $adModel->getCitrusId();
                $collections->removeItemByKey($id);
                $collections->addItem($product);
                $adProductIds[$citrus_ad_id] = $id;
            }
        }
        return $adProductIds;
    }
    protected function _getProductCollection()
    {
        $collections = parent::_getProductCollection();
        $adsEnable = Mage::getStoreConfig('citrus_sync/citrus_ads/enable', Mage::app()->getStore());
        if($adsEnable) {
            try {
                $categoryAdResponse = Mage::registry('categoryAdResponse');
                $searchAdResponse = Mage::registry('searchAdResponse');
                $collections->getItems();
                $adProductIds = [];
                if ($categoryAdResponse) {
                    $adProductIds = $this->getAdResponse($categoryAdResponse['ads'], $collections);
                } elseif ($searchAdResponse) {
                    $adProductIds = $this->getAdResponse($searchAdResponse['ads'], $collections);
                }
                $items = $collections->getItems();
                foreach ($items as $key => $collection) {
                    if (in_array($key, $adProductIds)) {
                        $collection->addData(['ad_index' => '0']);
                        $collection->addData(['citrus_ad_id' => array_search($key, $adProductIds)]);
                    } else
                        $collection->addData(['ad_index' => '1']);
                }
                usort($items, array('Citrus_Integration_Block_Product_List', 'sortByIndex'));
                foreach ($items as $key => $item) {
                    $collections->removeItemByKey($item->getEntityId());
                    $collections->addItem($item);
                }
            } catch (Exception $e) {
                Mage::helper('citrusintegration')->log('Collection product error: ' . $e->getMessage(), __FILE__, __LINE__);
            }
        }
        return $collections;
    }

    public static function sortByIndex($a, $b)
    {
        if($a->getAdIndex() ==  $b->getAdIndex()){
            return 0 ;
        }
        return ($a->getAdIndex() < $b->getAdIndex()) ? -1 : 1;
    }
}
