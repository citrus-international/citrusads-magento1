<?php

class Citrus_Integration_Model_System_Config_Source_Crontime
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 5, 'label'=>Mage::helper('adminhtml')->__('5 minutes')),
            array('value' => 10, 'label'=>Mage::helper('adminhtml')->__('10 minutes')),
            array('value' => 120, 'label'=>Mage::helper('adminhtml')->__('2 hours'))
        );
    }
}
