<?php

class Citrus_Integration_Block_Product_List extends Mage_Catalog_Block_Product_List
{
    /**
     * Default toolbar block name
     *
     * @var string
     */
    protected $_defaultToolbarBlock = 'catalog/product_list_toolbar';

    private $productsLoaded = 0;

    public function getAdId2SkuMap($responses)
    {
        $map = array();
        foreach ($responses as $response) {
            $adModel = Mage::getModel(Citrus_Integration_Model_Ad::class)->load($response);
            $sku = $adModel->getGtin();
            if ($sku) {
                $citrusAdId = $adModel->getData('citrus_id');
                $map[$citrusAdId] = $sku;
            }
        }
        return $map;
    }

    public function getOrderbyExpr($skus)
    {
        $expr = '';
        $conn = Mage::getSingleton('core/resource')->getConnection('default_read');
        foreach ($skus as $sku) {
            if ($sku) {
                $expr = $expr . ($expr ? ',' : '') . 'sku=' . $conn->quote($sku) . ' DESC';
            }
        }
        return $expr;
    }

    public function getOrSkuInExpr($skus)
    {
        $expr = '';
        $conn = Mage::getSingleton('core/resource')->getConnection('default_read');
        foreach ($skus as $sku) {
            if ($sku) {
                $expr = $expr . ($expr ? ',' : '') . $conn->quote($sku);
            }
        }
        return $expr ? ' or sku in (' . $expr . ')' : '';
    }

    protected function _getProductCollection()
    {
        $collections = parent::_getProductCollection();

        if (!$this->productsLoaded) {
            /** @var Mage_Catalog_Block_Product_List_Toolbar $toolbar */
            $toolbar = Mage::getBlockSingleton(Mage_Catalog_Block_Product_List_Toolbar::class);
            try {
                $collections->setPage((int)$toolbar->getCurrentPage(), (int)$toolbar->getLimit());
                $categoryAdResponse = Mage::registry('categoryAdResponse');
                $searchAdResponse = Mage::registry('searchAdResponse');
                $collections->addAttributeToSelect('sku');

                $adId2SkuMap = array();
                if ($categoryAdResponse) {
                    $adId2SkuMap = $this->getAdId2SkuMap($categoryAdResponse['ads']);
                } elseif ($searchAdResponse) {
                    $adId2SkuMap = $this->getAdId2SkuMap($searchAdResponse['ads']);
                    if ($adId2SkuMap) {
                        $from = $collections->getSelect()->getPart(Zend_Db_Select::FROM);
                        $from['search_result']['joinType'] = 'left join';
                        $searchResultJoinConditions = explode(' AND ', $from['search_result']['joinCondition']);
                        $collections->getSelect()
                            ->setPart(Zend_Db_Select::FROM, $from)
                            ->where($searchResultJoinConditions[1] . $this->getOrSkuInExpr($adId2SkuMap))
                            ->where("search_result.query_id is not null" . $this->getOrSkuInExpr($adId2SkuMap));
                    }
                }

                $collections->getSelect()->order(new Zend_Db_Expr($this->getOrderbyExpr($adId2SkuMap)));
//                Mage::helper('citrusintegration')->log('==== SQL: ' . $collections->getSelect(), __FILE__, __LINE__);
                $productItems = $collections->getItems();

                $session = Mage::getSingleton('customer/session');
                $persistentCitrusAdIdArray = $session->getData('citrusAdIds');
                if (!isset($persistentCitrusAdIdArray)) {
                    $persistentCitrusAdIdArray = array();
                }
                foreach ($productItems as $productItem) {
                    if (in_array($productItem->getSku(), $adId2SkuMap)) {
                        $citrusAdId = array_search($productItem->getSku(), $adId2SkuMap);
                        $productItem->addData(array('citrus_ad_id' => $citrusAdId));
                        $persistentCitrusAdIdArray[$productItem->getSku()] = $citrusAdId;
                    }
                }

                $session->setData('citrusAdIds', $persistentCitrusAdIdArray);
//                Mage::helper('citrusintegration')->log('citrus_ad_id_array: '. json_encode($persistentCitrusAdIdArray), __FILE__, __LINE__);
                $this->productsLoaded = 1;
            } catch (Exception $e) {
                Mage::helper('citrusintegration')->log('Collection product error: ' . $e->getMessage(), __FILE__, __LINE__);
                Mage::helper('citrusintegration')->log('Collection product error - trace: ' . $e->getTraceAsString(), __FILE__, __LINE__);
            }
        }

        return $collections;
    }

}
