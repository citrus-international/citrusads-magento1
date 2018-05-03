<?php

class Citrus_Integration_Model_System_Config_Source_Option
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 0, 'label'=>Mage::helper('adminhtml')->__('Real time')),
            array('value' => 1, 'label'=>Mage::helper('adminhtml')->__('Cron job')),
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
            0 => Mage::helper('adminhtml')->__('Real time'),
            1 => Mage::helper('adminhtml')->__('Cron job')
        );
    }

}
