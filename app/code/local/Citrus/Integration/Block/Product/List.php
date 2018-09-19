<?php

class Citrus_Integration_Block_Product_List extends Mage_Catalog_Block_Product_List
{
    /**
     * Default toolbar block name
     *
     * @var string
     */
    protected $_defaultToolbarBlock = 'catalog/product_list_toolbar';

    public function getAdResponse($responses,$collections, $classType)
    {
        $adProductIds = array();
        foreach ($responses as $response){
            $adModel = Mage::getModel(Citrus_Integration_Model_Ad::class)->load($response);
            $sku = $adModel->getGtin();
            /** @var Mage_Catalog_Model_Product $productModel */
            $productModel = Mage::getModel($classType);
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
        /** @var Mage_Catalog_Block_Product_List_Toolbar $toolbar */
        $toolbar = Mage::getBlockSingleton(Mage_Catalog_Block_Product_List_Toolbar::class);
        /** @var Mage_Catalog_Model_Layer $layer */
        $layer = Mage::getModel(Mage_Catalog_Model_Layer::class);
        try{
            $collections->setPageSize((int)$toolbar->getLimit());
            $categoryAdResponse = Mage::registry('categoryAdResponse');
            $searchAdResponse = Mage::registry('searchAdResponse');
            $collections->getItems();
            $adProductIds = array();
            $classType = get_class($collections->getFirstItem());
            Mage::helper('citrusintegration')->log('Type class: '. $classType, __FILE__, __LINE__);
            if($categoryAdResponse){
                $adProductIds = $this->getAdResponse($categoryAdResponse['ads'], $collections, $classType);
            }elseif($searchAdResponse){
                $adProductIds = $this->getAdResponse($searchAdResponse['ads'], $collections, $classType);
            }

            $productItems = $collections->getItems();
            foreach ($productItems as $key => $productItem){
                if(in_array($key, $adProductIds)){
                    $productItem->addData(array('ad_index' => '0'));
                    $productItem->addData(array('citrus_ad_id' => array_search($key, $adProductIds)));
                }else
                    $productItem->addData(array('ad_index' => '1'));
            }

            usort($productItems, array('Citrus_Integration_Block_Product_List','sortByIndex'));
            foreach ($productItems as $key => $productItem) {
                $collections->removeItemByKey($productItem->getEntityId());
                $collections->addItem($productItem);
            }
        }catch (Exception $e){
            Mage::helper('citrusintegration')->log('Collection product error: '.$e->getMessage(), __FILE__, __LINE__);
            Mage::helper('citrusintegration')->log('Collection product error - trace: '.$e->getTraceAsString(), __FILE__, __LINE__);
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
