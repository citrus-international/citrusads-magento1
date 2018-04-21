<?php
class Citrus_Integration_Block_Adminhtml_Citrusintegration_Ad extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_citrusintegration_ad';
        $this->_blockGroup = 'citrusintegration';
        $this->_addButton('add_new', array(
            'label'   => Mage::helper('catalog')->__('Get Ads'),
            'onclick' => "setLocation('{$this->getUrl('*/*/request')}')",
            'class'   => 'add'
        ));
        $this->_headerText = Mage::helper('adminhtml')->__('Ads List');
        parent::__construct();
        $this->_removeButton('add');
    }
    protected function _prepareLayout()
    {
        $this->setChild('grid',$this->getLayout()->createBlock( $this->_blockGroup.'/' . $this->_controller . '_grid', $this->_controller . '.grid')->setSaveParametersInSession(true) );
        return parent::_prepareLayout();
    }
}