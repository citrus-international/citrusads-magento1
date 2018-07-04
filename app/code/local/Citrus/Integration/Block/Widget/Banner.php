<?php
class Citrus_Integration_Block_Widget_Banner extends Mage_Core_Block_Template implements Mage_Widget_Block_Interface
{
    public function _construct()
    {
        $this->setTemplate('citrus/widget/banner.phtml');
    }
    public function getPageType()
    {
        return $this->getData('page_type');
    }
    public function getHeight()
    {
        return $this->getData('height');
    }
    public function getWidth()
    {
        return $this->getData('width');
    }
    public function getBanners($pageType)
    {
        return $this->getCitrusHelper()->getBannerModel()->getBannerByPageType($pageType);
    }
    public function getCitrusHelper()
    {
        return Mage::helper('citrusintegration');
    }
}