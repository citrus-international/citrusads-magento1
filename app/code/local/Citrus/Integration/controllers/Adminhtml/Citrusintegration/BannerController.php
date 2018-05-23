<?php

class Citrus_Integration_Adminhtml_Citrusintegration_BannerController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->_title($this->__('Banners List'));
        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('citrusintegration/adminhtml_citrusintegration_banner'));
        $this->renderLayout();
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