<?php

class Citrus_Integration_Adminhtml_Citrusintegration_QueueController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        // Let's call our initAction method which will set some basic params for each action
        $this->_title($this->__('Queue List'));
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->renderLayout();
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
    /**
     * @return false|Citrus_Integration_Helper_Data
     */
    protected function getHelper(){
        return Mage::helper('citrusintegration/data');
    }
    /**
     * @return false|Citrus_Integration_Model_Queue
     */
    protected function getQueueModel(){
        return Mage::getModel('citrusintegration/queue');
    }
    public function productAction(){
        $enable = Mage::getStoreConfig('citrus_sync/citrus_product/enable', Mage::app()->getStore());
        $catalogId = $this->getCitrusCatalogId();
        $teamId = $this->getHelper()->getTeamId();
        if(!$catalogId || !$teamId){
            $message = Mage::helper('adminhtml')->__('Please save your api key first!');
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
                    foreach ($collections as $collection) {
                        $this->pushItemToQueue($collection);
                    }
                }
                $message = Mage::helper('adminhtml')->__('All Products have been added to queue, click here to go to check out sync queue');
            }
            else {
                $message = Mage::helper('adminhtml')->__('Please enable sync product!');
            }
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(['message'=>$message]));
    }
    public function orderAction(){
        $enable = Mage::getStoreConfig('citrus_sync/citrus_order/enable', Mage::app()->getStore());
        $status = Mage::getStoreConfig('citrus_sync/citrus_order/type_order', Mage::app()->getStore());
        $catalogId = $this->getCitrusCatalogId();
        $teamId = $this->getHelper()->getTeamId();
        if(!$catalogId || !$teamId){
            $message = Mage::helper('adminhtml')->__('Please save your api key first!');
        }
        else {
            if ($enable) {
                /** @var Mage_Catalog_Model_Product $productModel */
                $orderModel = Mage::getModel('sales/order');
                if($status != ''){
                    $allCollections = $orderModel->getCollection()
                        ->addAttributeToFilter('status', array('eq' => $status))
                        ->setPageSize(100);
                }
                else {
                    $allCollections = $orderModel->getCollection()
                        ->setPageSize(100);
                }
                $numberOfPages = $allCollections->getLastPageNumber();
                for ($i = 1; $i <= $numberOfPages; $i++) {
                    $collections = $allCollections->setCurPage($i);
                    foreach ($collections as $collection) {
                        $this->pushItemToQueue($collection);
                    }
                }
                $message = Mage::helper('adminhtml')->__('All Orders have been added to queue, click here to go to check out sync queue');
            }
            else {
                $message = Mage::helper('adminhtml')->__('Please enable sync order!');
            }
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(['message'=>$message]));
    }
    public function customerAction(){
        $enable = Mage::getStoreConfig('citrus_sync/citrus_customer/enable', Mage::app()->getStore());
        $catalogId = $this->getCitrusCatalogId();
        $teamId = $this->getHelper()->getTeamId();
        if(!$catalogId || !$teamId){
            $message = Mage::helper('adminhtml')->__('Please save your api key first!');
        }
        else {
            if ($enable) {
                /** @var Mage_Catalog_Model_Product $productModel */
                $customerModel = Mage::getModel("customer/customer");
                $allCollections = $customerModel->getCollection()
                    ->addAttributeToSelect('*')
                    ->setPageSize(100);
                $numberOfPages = $allCollections->getLastPageNumber();
                for ($i = 1; $i <= $numberOfPages; $i++) {
                    $collections = $allCollections->setCurPage($i);
                    foreach ($collections as $collection) {
                        $this->pushItemToQueue($collection);
                    }
                }
                $message = Mage::helper('adminhtml')->__('All Customers have been added to queue, click here to go to check out sync queue');
            }
            else {
                $message = Mage::helper('adminhtml')->__('Please enable sync customer!');
            }
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(['message'=>$message]));
    }
    public function pushItemToQueue($item){
        $queueModel = $this->getQueueModel();
        $queueCollection = $queueModel->getCollection()->addFieldToSelect('id')
            ->addFieldToFilter('entity_id', ['eq' => $item->getId()])
            ->addFieldToFilter('type', ['eq' => $item->getResourceName()])
            ->getFirstItem();
        if($queueCollection->getData()){
            $queueModel->load($queueCollection->getId());
            $queueModel->enqueue($item->getId(), $item->getResourceName());
        }else {
            $queueModel->enqueue($item->getId(), $item->getResourceName());
        }
    }
    public function newAction()
    {
        // We just forward the new action to a blank edit form
        $this->_forward('edit');
    }

    public function editAction()
    {
        $this->_initAction();

        // Get id if available
        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('citrusintegration/queue');

        if ($id) {
            // Load record
            $model->load($id);
            // Check if record is loaded
            if (!$model->getId()) {
                Mage::getSingleton('adminhtml/session')->addError($this->__('This baz no longer exists.'));
                $this->_redirect('*/*/');

                return;
            }
        }

        $this->_title($model->getId() ? $model->getName() : $this->__('New Queue'));

        $data = Mage::getSingleton('adminhtml/session')->getBazData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        Mage::register('citrusintegration', $model);

        $this->_initAction()
            ->_addBreadcrumb($id ? $this->__('Edit Baz') : $this->__('New Baz'), $id ? $this->__('Edit Baz') : $this->__('New Queue'))
            ->_addContent($this->getLayout()->createBlock('citrusintegration/adminhtml_queue_edit')->setData('action', $this->getUrl('*/*/save')))
            ->renderLayout();
    }

    public function saveAction()
    {
        if ($postData = $this->getRequest()->getPost()) {
            $model = Mage::getSingleton('citrusintegration/queue');
            $model->setData($postData);

            try {
                $model->save();

                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The queue has been saved.'));
                $this->_redirect('*/*/');

                return;
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($this->__('An error occurred while saving this queue.'));
            }

            Mage::getSingleton('adminhtml/session')->setBazData($postData);
            $this->_redirectReferer();
        }
    }

    public function massDeleteAction() {
        $requestIds = $this->getRequest()->getParam('id');
        if(!is_array($requestIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select request(s)'));
        } else {
            try {
                foreach ($requestIds as $requestId) {
                    $RequestData = Mage::getModel('citrusintegration/queue')->load($requestId);
                    $RequestData->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                        'Total of %d record(s) were successfully deleted', count($requestIds)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/');
    }

    public function messageAction()
    {
        $data = Mage::getModel('citrusintegration/queue')->load($this->getRequest()->getParam('id'));
        echo $data->getContent();
    }

    /**
     * Initialize action
     *
     * Here, we set the breadcrumbs and the active menu
     *
     * @return Mage_Adminhtml_Controller_Action
     */
    protected function _initAction()
    {
        $this->loadLayout()
            // Make the active menu match the menu config nodes (without 'children' inbetween)
            ->_setActiveMenu('citrus/citrus_queue')
            ->_title($this->__('Sales'))->_title($this->__('Queue'))
            ->_addBreadcrumb($this->__('Sales'), $this->__('Sales'))
            ->_addBreadcrumb($this->__('Baz'), $this->__('Baz'));

        return $this;
    }

    /**
     * Check currently called action by permissions for current user
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('citrus/citrus_queue');
    }
}