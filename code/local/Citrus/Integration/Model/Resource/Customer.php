<?php

class Citrus_Integration_Model_Resource_Customer extends Mage_Core_Model_Resource_Db_Abstract
{
    public function _construct()
    {
        $this->_init('citrusintegration/customer', 'id');
    }

    /**
     * Get catalog_id by name
     *
     * @param string $entityId
     * @return string|false
     */
    public function getCustomerIdByEntityId($entityId)
    {
        $adapter = $this->_getReadAdapter();

        $select = $adapter->select()
            ->from(self::getMainTable(), 'citrus_id')
            ->where('entity_id = :entityId');

        $bind = array(':entityId' => $entityId);
        return $adapter->fetchOne($select, $bind);
    }
}