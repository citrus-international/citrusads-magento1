<?php

class Citrus_Integration_Model_Resource_Queue extends Mage_Core_Model_Resource_Db_Abstract
{
    public function _construct()
    {
        $this->_init('citrusintegration/queue', 'id');
    }
    public function getCount()
    {
        $adapter = $this->_getReadAdapter();
        return $adapter->fetchOne('SELECT COUNT(*) FROM '.self::getMainTable());
    }
}