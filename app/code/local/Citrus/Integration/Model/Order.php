<?php

class Citrus_Integration_Model_Order extends Mage_Core_Model_Abstract
{
    /**
     * Entity code.
     * Can be used as part of method name for entity processing
     */
    const ENTITY                 = 'order';
    public function _construct()
    {
        parent::_construct();
        $this->_init('citrusintegration/order');
    }
    protected function _getResource()
    {
        return parent::_getResource();
    }
}