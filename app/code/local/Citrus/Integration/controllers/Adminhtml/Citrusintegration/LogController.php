<?php

class Citrus_Integration_Adminhtml_Citrusintegration_LogController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->_title($this->__('Log List'));
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->_addContent($this->getLayout()->createBlock('citrusintegration/adminhtml_citrusintegration_log'));
        $this->renderLayout();
    }
    /**
     * @return false|Citrus_Integration_Model_Service_Request
     */
    protected function getRequestModel()
    {
        return Mage::getModel('citrusintegration/service_request');
    }
    /**
     * @return false|Mage_Catalog_Model_Product
     */
    protected function getProductModel()
    {
        return Mage::getModel('catalog/product');
    }
    /**
     * @return false|Mage_Customer_Model_Customer
     */
    protected function getCustomerModel()
    {
        return Mage::getModel("customer/customer");
    }
    /**
     * @return false|Mage_Sales_Model_Order
     */
    protected function getOrderModel()
    {
        return Mage::getModel('sales/order');
    }
    /**
     * @return false|Citrus_Integration_Helper_Data
     */
    protected function getHelper()
    {
        return Mage::helper('citrusintegration/data');
    }
    /**
     * @return false|Citrus_Integration_Model_Queue
     */
    protected function getQueueModel()
    {
        return Mage::getModel('citrusintegration/queue');
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
            ->_setActiveMenu('citrus')
            ->_title($this->__('Citrus'))->_title($this->__('Queue'))
            ->_addBreadcrumb($this->__('Citrus'), $this->__('Citrus'))
            ->_addBreadcrumb($this->__('Queue'), $this->__('Queue'));

        return $this;
    }

    /**
     * Check currently called action by permissions for current user
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('citrus/citrus_log');
    }
}