<?php

class Citrus_Integration_Model_Resource_Ad extends Mage_Core_Model_Resource_Db_Abstract
{
    public function _construct()
    {
        $this->_init('citrusintegration/ad', 'id');
    }
    public function getIdByCitrusId($citrusId){
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
            ->from(self::getMainTable(), 'id')
            ->where('citrus_id = :citrusId');
        $bind = array(
            ':citrusId' => (string)$citrusId
        );
        return $adapter->fetchOne($select, $bind);
    }
}