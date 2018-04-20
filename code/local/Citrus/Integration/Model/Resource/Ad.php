<?php

class Citrus_Integration_Model_Resource_Ad extends Mage_Core_Model_Resource_Db_Abstract
{
    public function _construct()
    {
        $this->_init('citrusintegration/ad', 'id');
    }
}