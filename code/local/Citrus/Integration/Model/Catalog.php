<?php

class Citrus_Integration_Model_Catalog extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('citrusintegration/catalog');
    }
}