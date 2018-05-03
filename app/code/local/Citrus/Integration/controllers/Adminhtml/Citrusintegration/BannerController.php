<?php

class Citrus_Integration_Adminhtml_Citrusintegration_BannerController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->_title($this->__('Banners List'));
        $this->loadLayout();
        $this->_initAction();
        $this->_initLayoutMessages('customer/session');
        $this->_addContent($this->getLayout()->createBlock('citrusintegration/adminhtml_citrusintegration_banner'));
        $this->renderLayout();
    }
//    public function infoAction()
//    {
//        $this->_initAction();
//
//        $id  = $this->getRequest()->getParam('id');
//        $model = Mage::getModel('citrusintegration/ad');
//        $discountModel = Mage::getModel('citrusintegration/discount');
//        $bannerModel = Mage::getModel('citrusintegration/banner');
//        $relevantModel = Mage::getModel('citrusintegration/relevant');
//
//        if ($id) {
//            $model->load($id);
//            if (!$model->getId()) {
//                Mage::getSingleton('adminhtml/session')->addError($this->__('This ad no longer exists.'));
//                $this->_redirect('*/*/');
//                return;
//            }
//            else{
//                $discountModel->load($model->getData('discount_id'));
//                $bannerModel->load($model->getData('banner_id'));
//                $relevantModel->load($model->getData('relevant_id'));
//            }
//        }
//
//        $this->_title($model->getId() ? $model->getName() : $this->__('New Baz'));
//
//        $data = Mage::getSingleton('adminhtml/session')->getBazData(true);
//        if (!empty($data)) {
//            $model->setData($data);
//        }
//        try{
//            Mage::register('citrus_ad', $model);
//        }catch (Exception $e){}
//
//
//        $this->_initAction()
//            ->_addContent($this->getLayout()->createBlock('citrusintegration/adminhtml_citrusintegration_ad_info'))
//            ->renderLayout();
//    }
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
            ->_title($this->__('Citrus'))->_title($this->__('Banners'));

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