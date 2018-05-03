<?php

class Citrus_Integration_Model_Discount extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('citrusintegration/discount');
    }

    protected function _getResource()
    {
        return parent::_getResource();
    }
}