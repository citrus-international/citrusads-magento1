<?php

class Citrus_Integration_Model_System_Config_Backend_Product_Attribute extends Mage_Core_Model_Config_Data
{
    protected function _afterSave()
    {
        if ($this->isValueChanged()) {
            Mage::getSingleton('adminhtml/session')
                ->addNotice(
                    Mage::helper('adminhtml')
                    ->__('You need re-sync products to apply this change! Click <a id="queue_continue" href="' . Mage::helper("adminhtml")->getUrl("adminhtml/citrusintegration_queue/product") . 'redirect/true">here</a> to continue')
                );
        }
    }
}