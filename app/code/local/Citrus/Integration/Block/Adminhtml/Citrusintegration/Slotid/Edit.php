<?php
class Citrus_Integration_Block_Adminhtml_Citrusintegration_Slotid_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    /**
     * Init class
     */
    public function __construct()
    {
        $this->_blockGroup = 'citrusintegration';
        $this->_controller = 'adminhtml_citrusintegration_slotid';

        parent::__construct();

//        $this->_removeButton('save');
//        $this->_removeButton('delete');
    }

    /**
     * Get Header text
     *
     * @return string
     */
    public function getHeaderText()
    {
       return $this->__('SlotId');
    }
}