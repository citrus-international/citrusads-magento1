<?php

class Citrus_Integration_Model_Resource_Log extends Mage_Core_Model_Resource_Db_Abstract
{
    public function _construct()
    {
        $this->_init('citrusintegration/log', 'id');
    }
    /**
     * @return Mage_Core_Helper_Abstract|Citrus_Integration_Helper_Data
     */
    protected function getHelper()
    {
        return Mage::helper('citrusintegration/data');
    }
}