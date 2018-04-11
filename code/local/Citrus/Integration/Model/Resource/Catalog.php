<?php

class Citrus_Integration_Model_Resource_Catalog extends Mage_Core_Model_Resource_Db_Abstract
{
    public function _construct()
    {
        $this->_init('citrusintegration/catalog', 'id');
    }
}