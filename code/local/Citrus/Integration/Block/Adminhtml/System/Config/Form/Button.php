<?php

class Citrus_Integration_Block_Adminhtml_System_Config_Form_Button extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected $_buttonLabel = 'Sync now';

    /*
     * Set template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('citrus/integration/system/config/button.phtml');
    }

    /**
     * Return element html
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $originalData = $element->getOriginalData();
        $buttonLabel = !empty($originalData['label']) ? $originalData['label'] : $this->_buttonLabel;
        $router = !empty($originalData['button_url']) ? $originalData['button_url'] : '*/dashboard/index';
        $this->addData(
            [
                'label' => $this->helper('adminhtml')->__($buttonLabel),
                'id' => $element->getHtmlId(),
                'button_url' => Mage::helper('adminhtml')->getUrl($router, array('_secure' => true))//$this->getUrl($router)
            ]
        );
        $element->setComment('<strong style="color:red">Warning</strong>: Please save the configuration before syncing data');
        return $this->_toHtml();
    }
}