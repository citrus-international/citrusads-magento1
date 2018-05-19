<?php

class Citrus_Integration_Model_Discount extends Mage_Core_Model_Abstract
{
    /**
     * Entity code.
     * Can be used as part of method name for entity processing
     */
    const ENTITY                 = 'discount';
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