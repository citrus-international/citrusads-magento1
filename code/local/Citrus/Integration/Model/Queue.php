<?php

class Citrus_Integration_Model_Queue extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('citrusintegration/queue');
    }
    protected function _getResource()
    {
        return parent::_getResource();
    }
}