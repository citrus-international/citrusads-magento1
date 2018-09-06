<?php
class Citrus_Integration_Model_Sync
{

    /**
     * @return false|Citrus_Integration_Model_Queue
     */
    protected function getQueueModel()
    {
        return Mage::getModel('citrusintegration/queue');
    }
    /**
     * @return false|Citrus_Integration_Model_Service_Request
     */
    protected function getRequestModel()
    {
        return Mage::getModel('citrusintegration/service_request');
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
    protected function getHelper()
    {
        return Mage::helper('citrusintegration/data');
    }
    public function syncData($type)
    {
        Mage::log('run sync data'.$type, null, 'citrus.log', true);
        $queueModel = $this->getQueueModel();
        $collectionDatas = $queueModel->getCollection()->addFieldToSelect('entity_id')
            ->addFieldToFilter('type', array('eq' => $type))->setPageSize(100)->setCurPage(1);
            $syncItems = new Varien_Object;
            $catalog_product = array();
            $sales_order = array();
            $customer_customer = array();
            foreach ($collectionDatas as $collection){
                if($type == 'catalog/product'){
                    $catalog_product[] = $collection->getEntityId();
                    $collection->delete();
                }
                elseif($type == 'sales/order') {
                    $sales_order[] = $collection->getEntityId();
                    $collection->delete();
                }
                elseif($type == 'customer/customer') {
                    $customer_customer[] = $collection->getEntityId();
                    $collection->delete();
                }
            }

            $syncItems->addData(array('catalog_product' => $catalog_product));
            $syncItems->addData(array('sales_order' => $sales_order));
            $syncItems->addData(array('customer_customer' => $customer_customer));
            $this->pushSyncItem($syncItems);
    }
    protected function pushSyncItem($syncItems)
    {
        $catalog_product = $syncItems->getCatalogProduct();
        $sales_order = $syncItems->getSalesOrder();
        $customer_customer = $syncItems->getCustomerCustomer();
        if($catalog_product){
            $bodyCatalogProducts = array();
            $bodyProducts = array();
            /** @var Mage_Catalog_Model_Product $productModel */
            $productModel = Mage::getModel('catalog/product');
            $productCollection = $productModel->getCollection()->addAttributeToSelect('*')
                ->addAttributeToFilter('entity_id', array('in' => $catalog_product));
            foreach ($productCollection as $product){
                $catalogProductData = $this->getHelper()->getCatalogProductData($product);
                foreach ($catalogProductData as $key => $oneData){
                    $bodyCatalogProducts[$key] = array_merge(isset($bodyCatalogProducts[$key]) ? $bodyCatalogProducts[$key] : $bodyCatalogProducts[$key] = array(), array($oneData));
                }

                $bodyProducts[] = $this->getHelper()->getProductData($product);
            }

            unset($productCollection);
            foreach ($bodyCatalogProducts as $bodyCatalogProduct){
                $pageCatalogProduct = count($bodyCatalogProduct)/100;
                for ($i = 0;$i <= $pageCatalogProduct; $i++){
                    $bodyCatalogProductsPage = array_slice($bodyCatalogProduct, $i*100, 100);
                    if(!empty($bodyCatalogProductsPage)){
                        $responseCatalogProduct = $this->getRequestModel()->pushCatalogProductsRequest($bodyCatalogProductsPage);//$bodyCatalogProductsPage
                        $this->getHelper()->log('cron - sync catalog product: '.$responseCatalogProduct['message'], __FILE__, __LINE__);
                        $this->getHelper()->log('cron - sync catalog product body: '.json_encode($bodyCatalogProductsPage), __FILE__, __LINE__);
                    }
                }
            }

            $pageProduct = count($bodyProducts)/100;
            for ($i = 0;$i <= $pageProduct; $i++) {
                $bodyProductsPage = array_slice($bodyProducts, $i * 100, 100);
                if (!empty($bodyProductsPage)) {
                    $responseProduct = $this->getRequestModel()->pushProductsRequest($bodyProductsPage);
                    if($responseProduct['success']){
                        $queueModel = $this->getQueueModel();
                        $queueModel->makeDeleteItems($catalog_product, 'catalog/product');
                    }

                    $this->getHelper()->log('cron - sync product: ' . $responseProduct['message'], __FILE__, __LINE__);
                    $this->getHelper()->log('cron - sync product body: ' . json_encode($bodyProductsPage), __FILE__, __LINE__);
                }
            }
        }

        if($sales_order){
            $body = array();
            /** @var Mage_Sales_Model_Order $orderModel */
            $orderModel = Mage::getModel(Mage_Sales_Model_Order::class);
            $orderIncrementId = array();
            foreach ($sales_order as $orderItem){
                $orderIncrementId[] = $orderItem->getEntityId();
            }

            if($orderIncrementId){
                $orderCollection = $orderModel->getCollection()->addAttributeToSelect('*')
                    ->addAttributeToFilter('increment_id', array('in' => $orderIncrementId));
                foreach ($orderCollection as $order){
                    $data = $this->getHelper()->getOrderData($order);
                    $body[] = $data;
                }

                unset($orderCollection);
            }

            if(!empty($body)) {
                $response = $this->getRequestModel()->pushOrderRequest($body);
                if($response['success']){
                    $queueModel = $this->getQueueModel();
                    $queueModel->makeDeleteItems($sales_order, 'sales/order');
                }

                $this->getHelper()->handleResponse($response, 'order', $orderIncrementId);
                $this->getHelper()->log('cron - sync sales order: ' . $response['message'], __FILE__, __LINE__);
            }
        }

        if($customer_customer){
            $body = array();
            $customerModel = Mage::getModel(Mage_Customer_Model_Customer::class);
            $customerIds = array();
            foreach ($customer_customer as $customerItem){
                $customerIds[] = $customerItem->getEntityId();
            }

            if($customerIds){
                $customerCollection = $customerModel->getCollection()->addAttributeToSelect('*')
                    ->addAttributeToFilter('entity_id', array('in' => $customerIds));
                foreach ($customerCollection as $customer){
                    $data = $this->getHelper()->getOrderData($customer);
                    $body[] = $data;
                }

                unset($customerCollection);
            }

            if(!empty($body)) {
                $response = $this->getRequestModel()->pushCustomerRequest($body);
                if($response['success']){
                    $queueModel = $this->getQueueModel();
                    $queueModel->makeDeleteItems($customer_customer, 'customer/customer');
                }

                $this->getHelper()->handleResponse($response, 'customer', $customerIds);
                $this->getHelper()->log('cron - sync sales order: ' . $response['message'], __FILE__, __LINE__);
            }
        }
    }

}