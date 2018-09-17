<?php

class Citrus_Integration_Adminhtml_Citrusintegration_AdController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->_title($this->__('Ads List'));
        $this->loadLayout();
        $this->renderLayout();
    }
    public function infoAction()
    {
        $this->_initAction();

        $id  = $this->getRequest()->getParam('id');
        $model = Mage::getModel('citrusintegration/ad');
        $discountModel = Mage::getModel('citrusintegration/discount');
        $bannerModel = Mage::getModel('citrusintegration/banner');
        $relevantModel = Mage::getModel('citrusintegration/relevant');

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                Mage::getSingleton('adminhtml/session')->addError($this->__('This ad no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
            else{
                $discountModel->load($model->getData('discount_id'));
                $bannerModel->load($model->getData('banner_id'));
                $relevantModel->load($model->getData('relevant_id'));
            }
        }

        $this->_title($model->getId() ? $model->getName() : $this->__('New Ad'));

        $data = Mage::getSingleton('adminhtml/session')->getBazData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        try{
            Mage::unregister('citrus_ad');
            Mage::register('citrus_ad', $model);
        }catch (Exception $e){
            error_log('infoAction: '.$e->getMessage());
        }


        $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('citrusintegration/adminhtml_citrusintegration_ad_info'))
            ->renderLayout();
    }
    public function requestAction()
    {
        $this->loadLayout();
        Mage::unregister('is_banner');
        Mage::register('is_banner', $this->getRequest()->getParam('banner'), true);
        $this->_addContent($this->getLayout()->createBlock('citrusintegration/adminhtml_citrusintegration_ad_request'));
        $this->renderLayout();
    }
    public function sendAction()
    {
        $params = $this->getRequest()->getParams();
        $context = $this->getHelper()->getContextData($params);
        $response = $this->getRequestModel()->requestingAnAd($context);
        $return = $this->getHelper()->handleAdsResponse($response, $context['pageType']);
        if($return) {
            Mage::getSingleton('adminhtml/session')->addSuccess('Your request is completed');
            if($this->getRequest()->getParam('is_banner'))
                $this->_redirect('*/citrusintegration_banner/index');
            else $this->_redirect('*/*/index');
        }
        else{
            $this->_redirect('*/*/request');
        }
    }
    public function handleRelevant($relevants, $adId)
    {
        /** @var Citrus_Integration_Model_Relevant $model */
        $model = Mage::getModel('citrusintegration/relevant');
        $host = $this->getHelper()->getHost();

        if(!is_array($relevants)){
            $ids = $model->getIdByAdId($adId);
            if($ids){
                foreach ($ids as $id){
                    $model->load($id['id']);
                    try{
                        $model->delete();
                    }catch (Exception $e) {
                        Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                    }
                }
            }
        }
        else {
            foreach ($relevants as $relevant){
                $ids = $model->getIdByAdId($adId);
                if($ids){
                    foreach ($ids as $id){
                        $model->load($id['id']);
                        try{
                            $model->delete();
                        }catch (Exception $e) {
                            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                        }
                    }

                    $bannerData = array(
                        "id" => $relevant['id'],
                        "gtin" => $relevant['gtin'],
                        "ad_id" => $adId,
                        "host" => $host
                    );
                    $model->addData($bannerData);
                    try{
                        $model->save();
                    }catch (Exception $e) {
                        error_log('handleRelevant: ' . $e->getMessage());
                    }
                }
                else{
                    $bannerData = array(
                        "id" => $relevant['id'],
                        "gtin" => $relevant['gtin'],
                        "ad_id" => $adId,
                        "host" => $host
                    );
                    $model->addData($bannerData);
                    try{
                        $model->save();
                    }catch (Exception $e){
                        error_log('handleRelevant: ' . $e->getMessage());
                    }
                }
            }
        }
    }
    /**
     * @return false|Citrus_Integration_Model_Discount
     */
    protected function getDiscountModel()
    {
        return Mage::getModel('citrusintegration/discount');
    }
    /**
     * @return false|Citrus_Integration_Model_Ad
     */
    protected function getAdModel()
    {
        return Mage::getModel('citrusintegration/ad');
    }
    /**
     * @return false|Citrus_Integration_Model_Banner
     */
    protected function getBannerModel()
    {
        return Mage::getModel('citrusintegration/banner');
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