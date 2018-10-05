<?php

class Citrus_Integration_Block_Product_List extends Mage_Catalog_Block_Product_List
{
    /**
     * Default toolbar block name
     *
     * @var string
     */
    protected $_defaultToolbarBlock = 'catalog/product_list_toolbar';

    public function getAdId2SkuMap($responses)
    {
        $map = array();
        foreach ($responses as $response){
            $adModel = Mage::getModel(Citrus_Integration_Model_Ad::class)->load($response);
            $sku = $adModel->getGtin();
            if($sku) {
                $citrusAdId = $adModel->getData('citrus_id');
                $map[$citrusAdId] = $sku;
            }
        }
        return $map;
    }

    public function getOrderbyExprForAdSkus($skus) {
        $expr = '';
        foreach ($skus as $sku){
            if($sku) {
                $expr = $expr . ($expr?',':'') . 'sku=\'' . $sku . '\' DESC';
            }
        }
        return $expr;
    }

    protected function _getProductCollection()
    {

        $collections = parent::_getProductCollection();
        /** @var Mage_Catalog_Block_Product_List_Toolbar $toolbar */
        $toolbar = Mage::getBlockSingleton(Mage_Catalog_Block_Product_List_Toolbar::class);
        try{
            $collections->setPage((int)$toolbar->getCurrentPage(), (int)$toolbar->getLimit());
            $categoryAdResponse = Mage::registry('categoryAdResponse');
            $searchAdResponse = Mage::registry('searchAdResponse');
            $collections->addAttributeToSelect('sku');


            $adId2SkuMap = array();
            if($categoryAdResponse){
                $adId2SkuMap = $this->getAdId2SkuMap($categoryAdResponse['ads']);
            }elseif($searchAdResponse){
                $adId2SkuMap = $this->getAdId2SkuMap($searchAdResponse['ads']);
            }

            $collections->getSelect()->order(new Zend_Db_Expr($this->getOrderByExprForAdSkus($adId2SkuMap)));
//            Mage::helper('citrusintegration')->log('==== SQL: '. $collections->getse, __FILE__, __LINE__);
            $productItems = $collections->getItems();

            $session = Mage::getSingleton( 'customer/session' );
            $persistentCitrusAdIdArray = $session->getData('citrusAdIds');
            if (!isset($persistentCitrusAdIdArray)) {
                $persistentCitrusAdIdArray = array();
            }
            foreach ($productItems as $productItem){
                if(in_array($productItem->getSku(), $adId2SkuMap)){
                    $citrusAdId = array_search($productItem->getSku(), $adId2SkuMap);
                    $productItem->addData(array('citrus_ad_id' => $citrusAdId));
                    $persistentCitrusAdIdArray[$productItem->getSku()] = $citrusAdId;
                }
            }

            $session->setData( 'citrusAdIds', $persistentCitrusAdIdArray);
//            Mage::helper('citrusintegration')->log('citrus_ad_id_array: '. json_encode($persistentCitrusAdIdArray), __FILE__, __LINE__);

        }catch (Exception $e){
            Mage::helper('citrusintegration')->log('Collection product error: '.$e->getMessage(), __FILE__, __LINE__);
            Mage::helper('citrusintegration')->log('Collection product error - trace: '.$e->getTraceAsString(), __FILE__, __LINE__);
        }

        return $collections;
    }

}
