<?php

class Citrus_Integration_Model_System_Config_Source_Host
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 0, 'label'=>Mage::helper('adminhtml')->__('Staging')),
            array('value' => 1, 'label'=>Mage::helper('adminhtml')->__('AUS')),
            array('value' => 2, 'label'=>Mage::helper('adminhtml')->__('USA'))
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            0 => Mage::helper('adminhtml')->__('Staging'),
            1 => Mage::helper('adminhtml')->__('AUS'),
            2 => Mage::helper('adminhtml')->__('USA'),
        );
    }

}
