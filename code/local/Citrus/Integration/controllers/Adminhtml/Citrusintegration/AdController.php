<?php

class Citrus_Integration_Adminhtml_Citrusintegration_AdController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->_title($this->__('Ads List'));
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->renderLayout();
    }
    public function editAction()
    {
        $this->_initAction();

        $id  = $this->getRequest()->getParam('id');
        $model = Mage::getModel('citrusintegration/ad');

        if ($id) {
            $model->load($id);

            // Check if record is loaded
            if (!$model->getId()) {
                Mage::getSingleton('adminhtml/session')->addError($this->__('This baz no longer exists.'));
                $this->_redirect('*/*/');

                return;
            }
        }

        $this->_title($model->getId() ? $model->getName() : $this->__('New Baz'));

        $data = Mage::getSingleton('adminhtml/session')->getBazData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        Mage::register('foo_bar', $model);

        $this->_initAction()
            ->_addBreadcrumb($id ? $this->__('Edit Baz') : $this->__('New Baz'), $id ? $this->__('Edit Baz') : $this->__('New Baz'))
            ->_addContent($this->getLayout()->createBlock('citrusintegration/adminhtml_citrusintegration_ad_edit')->setData('action', $this->getUrl('*/*/save')))
            ->renderLayout();
    }
    public function requestAction(){
        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('citrusintegration/adminhtml_citrusintegration_ad_request'));
        $this->renderLayout();
    }
    public function sendAction(){
        $params = $this->getRequest()->getParams();
        $context = $this->getHelper()->getContextData($params);
        $resonse = $this->getRequestModel()->requestingAnAd($context);
        $this->handlePostResponse($resonse);
        $this->_redirect('*/*/');

    }
    public function handlePostResponse($response){
        if($response['success']){

            $data = json_decode($response['message'], true);
            $adModel = $this->getAdModel();
            $discountModel = $this->getDiscountModel();
            if($data['ads']){
               foreach ($data['ads'] as $ad){

                   $id = $adModel->getIdByCitrusId($ad['id']);
                   if($id){
                       $adModel->load($id);
                       $discountModel->load($adModel->getDiscountId());
                       $adData = [
                           'gtin' => $ad['gtin'],
                           'expiry' => $ad['expiry']
                       ];
                       $discountData = [
                            'amount' => $ad['discount']['amount'],
                            'minPrice' => $ad['discount']['minPrice'],
                            'maxPerCustomer' => $ad['discount']['maxPerCustomer'],
                       ];
                       $adModel->addData($adData);
                       $discountModel->addData($discountData);
                       try{
                           $discountModel->save();
                           $adModel->save();
                       }catch (Exception $e){}
                   }
                   else{
                       $discountData = [
                           'amount' => $ad['discount']['amount'],
                           'minPrice' => $ad['discount']['minPrice'],
                           'maxPerCustomer' => $ad['discount']['maxPerCustomer'],
                       ];
                       $discountModel->addData($discountData);
                       try{
                           $discountModel->save();
                       }catch (Exception $e){}
                       $adData = [
                           'citrus_id' => $ad['id'],
                           'discount_id' => $discountModel->getId(),
                           'gtin' => $ad['gtin'],
                           'expiry' => $ad['expiry']
                       ];
                       $adModel->addData($adData);
                       try{
                           $adModel->save();
                       }catch (Exception $e){}
                   }
               }
            }
        }
    }
    /**
     * @return false|Citrus_Integration_Model_Ad
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
        return Mage::getSingleton('admin/session')->isAllowed('citrus/citrus_ad');
    }
}