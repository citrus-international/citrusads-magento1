<?php
class Citrus_Integration_Model_Sync
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
    protected function getHelper(){
        return Mage::helper('citrusintegration/data');
    }
    public function syncData($type){
        Mage::log('run sync data'.$type,null, 'citrus.log', true);
        $queueModel = $this->getQueueModel();
        $collectionDatas = $queueModel->getCollection()->addFieldToSelect('entity_id')
            ->addFieldToFilter('type', ['eq' => $type])->setPageSize(100);
        $numberOfPages = $collectionDatas->getLastPageNumber();
        for ($i = 1; $i <= $numberOfPages; $i++) {
            $collections = $collectionDatas->setCurPage($i);
            $body = [];
            /** @var Mage_Catalog_Model_Product $collection */
            foreach ($collections as $collection) {
                $data = $this->getHelper()->handleData($collection, $type);
                $body[] = $data;
            }
            if($type == 'catalog/product'){
                $response = $this->getHelper()->getRequestModel()->pushCatalogProductsRequest([$body]);
                $this->getHelper()->handleResponse($response);
                $this->syncData('products');
            }
            elseif($type =='sales/order'){
                $response = $this->getHelper()->getRequestModel()->pushOrderRequest($body);
                $this->getHelper()->handleResponse($response);
            }
            elseif($type =='customer/customer') {
                $response = $this->getHelper()->getRequestModel()->pushCustomerRequest($body);
                $this->getHelper()->handleResponse($response);
            }
            elseif($type =='products') {
                $response = $this->getHelper()->getRequestModel()->pushProductsRequest([$body]);
                $this->getHelper()->handleResponse($response);
            }
        }
    }
}