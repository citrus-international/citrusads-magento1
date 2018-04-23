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

        $this->_title($model->getId() ? $model->getName() : $this->__('New Baz'));

        $data = Mage::getSingleton('adminhtml/session')->getBazData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
        try{
            Mage::register('citrus_ad', $model);
        }catch (Exception $e){}


        $this->_initAction()
//            ->_addBreadcrumb($id ? $this->__('Edit Baz') : $this->__('New Baz'), $id ? $this->__('Edit Baz') : $this->__('New Baz'))
            ->_addContent($this->getLayout()->createBlock('citrusintegration/adminhtml_citrusintegration_ad_info'))
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
        $response = $this->getRequestModel()->requestingAnAd($context);
        $this->handlePostResponse($response);
        $this->_redirect('*/*/');

    }
    public function handlePostResponse($response){
        if($response['success']){

            $data = json_decode($response['message'], true);
            $adModel = $this->getAdModel();
            $discountModel = $this->getDiscountModel();
            $host = $this->getHelper()->getHost();
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
                       $this->handleBanner($data['banners'] = isset($data['banners']) ? $data['banners'] : null, $id);
                       $this->handleBanner($data['products'], $id);
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
                           'expiry' => $ad['expiry'],
                           'host' => $host
                       ];
                       $adModel->addData($adData);
                       try{
                           $adModel->save();
                       }catch (Exception $e){
                           Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                       }
                   }
               }
            }
        }
    }
    public function handleBanner($banners, $adId){
        /** @var Citrus_Integration_Model_Banner $model */
        $model = Mage::getModel('citrusintegration/banner');
        $host = $this->getHelper()->getHost();
        if(!is_array($banners)){
            $ids = $model->getIdByAdId(1);
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
            foreach ($banners as $banner){
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
                    $bannerData = [
                        "id" => $banner['id'],
                        "slotId" => $banner['slotId'],
                        "imageUrl" => $banner['imageUrl'],
                        "linkUrl" => $banner['linkUrl'],
                        "altText" => $banner['altText'],
                        "expiry" => $banner['expiry'],
                        "ad_id" => $adId,
                        "host" => $host
                    ];
                    $model->addData($bannerData);
                    try{
                        $model->save();
                    }catch (Exception $e){
                        Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                    }
                }
                else{
                    $bannerData = [
                        "id" => $banner['id'],
                        "slotId" => $banner['slotId'],
                        "imageUrl" => $banner['imageUrl'],
                        "linkUrl" => $banner['linkUrl'],
                        "altText" => $banner['altText'],
                        "expiry" => $banner['expiry'],
                        "ad_id" => $adId,
                        "host" => $host
                    ];
                    $model->addData($bannerData);
                    try{
                        $model->save();
                    }catch (Exception $e){
                        Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                    }
                }
            }
        }
    }
    public function handleRelevant($relevants, $adId){
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
                    $bannerData = [
                        "id" => $relevant['id'],
                        "gtin" => $relevant['gtin'],
                        "ad_id" => $adId,
                        "host" => $host
                    ];
                    $model->addData($bannerData);
                    try{
                        $model->save();
                    }catch (Exception $e){}
                }
                else{
                    $bannerData = [
                        "id" => $relevant['id'],
                        "gtin" => $relevant['gtin'],
                        "ad_id" => $adId,
                        "host" => $host
                    ];
                    $model->addData($bannerData);
                    try{
                        $model->save();
                    }catch (Exception $e){}
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