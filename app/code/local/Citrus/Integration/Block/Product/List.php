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


    protected function _getProductCollection()
    {
        $collections = parent::_getProductCollection();
        $categoryAdResponse = Mage::registry('categoryAdResponse');
        $adProductIds = [];
        $collections->getItems();
        foreach ($categoryAdResponse['ads'] as $response){
            $id = Mage::getModel(Citrus_Integration_Model_Ad::class)->load($response)->getGtin();
            $product = Mage::getModel(Mage_Catalog_Model_Product::class)->load($id);

            $collections->removeItemByKey($id);
            $collections->addItem($product);
            $adProductIds[] = $id;
        }
        $items = $collections->getItems();
        foreach ($items as $key => $collection){
            if(in_array($key, $adProductIds)){
                $collection->addData(['ad_index' => 'a']);
            }else
                $collection->addData(['ad_index' => 'b']);
        }
        usort($items,array('Citrus_Integration_Block_Product_List','sortByIndex'));
        foreach ($items as $key => $item) {
            $collections->removeItemByKey($item->getEntityId());
            $collections->addItem($item);
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
