<?php

class Citrus_Integration_Model_Mysql4_Catalog extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('citrusintegration/catalog','id');
    }
}