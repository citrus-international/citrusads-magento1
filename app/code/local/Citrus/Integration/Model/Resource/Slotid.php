<?php

class Citrus_Integration_Model_Resource_Slotid extends Mage_Core_Model_Resource_Db_Abstract
{
    public function _construct()
    {
        $this->_init('citrusintegration/slotid', 'id');
    }
    public function getSlotIdById($entity_id, $page_type){
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
            ->from(self::getMainTable(), 'slot_id')
            ->where('page_type = :all_page')
            ->orWhere('page_id like :entity_id AND page_type = :page_type');
        $bind = array(
            ':page_type' => (string)$page_type,
            ':entity_id' => '%\"'.$entity_id.'\"%',
            ':all_page' => '0',
        );
        return $adapter->fetchAll($select, $bind);
    }
}