<?php

class Citrus_Integration_Adminhtml_Citrusintegration_SlotidController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->_title($this->__('SlotId List'));
        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('citrusintegration/adminhtml_citrusintegration_slotid'));
        $this->renderLayout();
    }
    public function editAction()
    {
        $id  = $this->getRequest()->getParam('id');
        $slotModel = Mage::getModel('citrusintegration/slotid')->load($id);

        if ($slotModel->getId() || $id == 0)
        {
            Mage::register('slotid_model', $slotModel);
            $this->loadLayout();
            $this->getLayout()->getBlock('head')
                ->setCanLoadExtJs(true);
            $this->_addContent($this->getLayout()
                ->createBlock('citrusintegration/adminhtml_citrusintegration_slotid_edit'));
            $this->renderLayout();
        }
        else
        {
            Mage::getSingleton('adminhtml/session')->addError('SlotId does not exist');
            $this->_redirect('*/*/');
        }
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function saveAction()
    {
        if ($this->getRequest()->getPost())
        {
            try {
                $postData = $this->getRequest()->getPost();
                $slotIdModel = Mage::getModel('citrusintegration/slotid');

                if( $this->getRequest()->getParam('id') <= 0 ) {

                    if($postData['page_type'] == '3')
                        $postData['page_id'] = ['search'];
                    $postData['page_id'] = json_encode($postData['page_id']);
                    unset($postData['form_key']);
                    $slotIdModel
                        ->addData($postData);
                    try {
                        $slotIdModel->save();
                    }catch (Exception $exception){
                    }

                    Mage::getSingleton('adminhtml/session')->addSuccess('Successfully saved');
                    Mage::getSingleton('adminhtml/session')->setfilmsData(false);
                    $this->_redirect('*/*/');
                    return;
                }else{
                    $slotIdModel->load($this->getRequest()->getParam('id'));

                    if($postData['page_type'] == '3')
                        $postData['page_id'] = ['search'];
                    $postData['page_id'] = json_encode($postData['page_id']);
                    unset($postData['form_key']);
                    $slotIdModel
                        ->addData($postData);
                    try {
                        $slotIdModel->save();
                    }catch (Exception $exception){
                    }
                    Mage::getSingleton('adminhtml/session')->addSuccess('Successfully saved');
                    Mage::getSingleton('adminhtml/session')->setfilmsData(false);
                    $this->_redirect('*/*/');
                    return;
                }

            } catch (Exception $e) {

                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setfilmsData($this->getRequest()->getPost());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
    }

    public function deleteAction()
    {
        if($this->getRequest()->getParam('id') > 0)
        {
            try
            {
                $slotIdModel = Mage::getModel('citrusintegration/slotid');
                $slotIdModel->setId($this->getRequest()->getParam('id'))->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess('successfully deleted');
                $this->_redirect('*/*/');
            }
            catch (Exception $e)
            {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }

    /**
     * @return false|Citrus_Integration_Model_Discount
     */
    protected function getDiscountModel(){
        return Mage::getModel('citrusintegration/discount');
    }
    /**
     * @return false|Citrus_Integration_Model_Ad
     */
    protected function getAdModel(){
        return Mage::getModel('citrusintegration/ad');
    }
    /**
     * @return false|Citrus_Integration_Model_Banner
     */
    protected function getBannerModel(){
        return Mage::getModel('citrusintegration/banner');
    }
    /**
     * @return false|Citrus_Integration_Model_Service_Request
     */
    protected function getRequestModel(){
        return Mage::getModel('citrusintegration/service_request');
    }
    /**
     * @return false|Mage_Catalog_Model_Product
     */
    protected function getProductModel(){
        return Mage::getModel('catalog/product');
    }
    /**
     * @return false|Mage_Customer_Model_Customer
     */
    protected function getCustomerModel(){
        return Mage::getModel("customer/customer");
    }
    /**
     * @return false|Mage_Sales_Model_Order
     */
    protected function getOrderModel(){
        return Mage::getModel('sales/order');
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
            ->_title($this->__('Citrus'))->_title($this->__('Ads'));

        return $this;
    }

    /**
     * Check currently called action by permissions for current user
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('citrus/citrus_ad');
    }
}