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
            $syncItems = new Varien_Object;
            $catalog_product = [];
            $sales_order = [];
            $customer_customer = [];
            foreach ($collections as $collection){
//                $type = $collection->getType();
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
            $syncItems->addData(['catalog_product' => $catalog_product]);
            $syncItems->addData(['sales_order' => $sales_order]);
            $syncItems->addData(['customer_customer' => $customer_customer]);
            $this->pushSyncItem($syncItems);
        }
    }
    protected function pushSyncItem($syncItems){
        $catalog_product = $syncItems->getCatalogProduct();
        $sales_order = $syncItems->getSalesOrder();
        $customer_customer = $syncItems->getCustomerCustomer();
        if($catalog_product){
            $bodyCatalogProducts = [];
            $bodyProducts = [];
            /** @var Mage_Catalog_Model_Product $productModel */
            $productModel = Mage::getModel(Mage_Catalog_Model_Product::class);
            foreach ($catalog_product as $productId){
                /** @var Mage_Catalog_Model_Product $product */
                $product = $productModel->load($productId);
                $bodyCatalogProducts[] = $this->getHelper()->getCatalogProductData($product);
                $bodyProducts[] = $this->getHelper()->getProductData($product);
            }
            $responseCatalogProduct = $this->getHelper()->getRequestModel()->pushCatalogProductsRequest($bodyCatalogProducts);
            $this->getHelper()->log('sync catalog product by cron: '.$responseCatalogProduct['message'], __FILE__, __LINE__);
            $responseProduct = $this->getHelper()->getRequestModel()->pushProductsRequest($bodyProducts);
            $this->getHelper()->log('sync product by cron: '.$responseProduct['message'], __FILE__, __LINE__);
        }
        if($sales_order){
            $body = [];
            /** @var Mage_Sales_Model_Order $orderModel */
            $orderModel = Mage::getModel(Mage_Sales_Model_Order::class);
            foreach ($sales_order as $orderIncrementId){
                /** @var Mage_Sales_Model_Order $order */
                $order = $orderModel->loadByIncrementId($orderIncrementId);
                $data = $this->getHelper()->getOrderData($order);
                $body[] = $data;
            }
            $response = $this->getHelper()->getRequestModel()->pushOrderRequest($body);
            $this->getHelper()->handleResponse($response, 'order', $sales_order);
            $this->getHelper()->log('sync sales order by cron: '.$response['message'], __FILE__, __LINE__);
        }
        if($customer_customer){
            $body = [];
            $customerModel = Mage::getModel(Mage_Customer_Model_Customer::class);
            foreach ($customer_customer as $customerId){
                /** @var Mage_Customer_Model_Customer $customer */
                $customer = $customerModel->load($customerId);
                $data = $this->getHelper()->getCustomerData($customer);
                $body[] = $data;
            }
            $response = $this->getHelper()->getRequestModel()->pushCustomerRequest($body);
            $this->getHelper()->handleResponse($response, 'customer', $customer_customer);
            $this->getHelper()->log('sync customer by cron: '.$response['message'], __FILE__, __LINE__);
        }
    }
}