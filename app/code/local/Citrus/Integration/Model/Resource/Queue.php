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
    public function makeDelete($ids, $type = null)
    {
        $adapter = $this->_getReadAdapter();
        if ($ids) {
            if ($type)
                $adapter->delete(self::getMainTable(), 'id in (' . implode(',', $ids) . ') and type = "' . $type . '"');
            else
                $adapter->delete(self::getMainTable(), 'id in (' . implode(',', $ids) . ')');
        }
    }
    public function makeDeleteItems($ids, $type)
    {
        $adapter = $this->_getReadAdapter();
        if ($ids) {
            $adapter->delete(self::getMainTable(), 'entity_id in (' . implode(',', $ids) . ') and type = "' . $type . '"');
        }
    }
}