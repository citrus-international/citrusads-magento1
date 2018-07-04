<?php
class Citrus_Integration_Block_Adminhtml_Citrusintegration_Banner extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_citrusintegration_banner';
        $this->_blockGroup = 'citrusintegration';

        $this->_headerText = Mage::helper('adminhtml')->__('Banners');
        $this->_addButton(
            'add_new', array(
            'label'   => Mage::helper('citrusintegration')->__('Request Ads and Banners'),
            'onclick' => "setLocation('{$this->getUrl('*/citrusintegration_ad/request/banner/1')}')",
            'class'   => 'add',
            'value' => 'banner'
            )
        );
        $this->_addButton(
            'ad_list', array(
            'label'   => Mage::helper('citrusintegration')->__('Ads List'),
            'onclick' => "setLocation('{$this->getUrl('*/citrusintegration_ad/index')}')",
            'class'   => 'go'
            )
        );

        parent::__construct();
        $this->_removeButton('add');
    }
    protected function _prepareLayout()
    {
        $this->setChild('grid', $this->getLayout()->createBlock($this->_blockGroup.'/' . $this->_controller . '_grid', $this->_controller . '.grid')->setSaveParametersInSession(true));
        return parent::_prepareLayout();
    }
}