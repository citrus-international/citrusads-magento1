<?php

class Citrus_Integration_Model_Resource_Catalog extends Mage_Core_Model_Resource_Db_Abstract
{
    public function _construct()
    {
        $this->_init('citrusintegration/catalog', 'id');
    }

    /**
     * Get catalog_id by name
     *
     * @param string $name
     * @return string|false
     */
    public function getCatalogIdByName($name)
    {
        $adapter = $this->_getReadAdapter();

        $select = $adapter->select()
            ->from(self::getMainTable(), 'catalog_id')
            ->where('name = :name');

        $bind = array(':name' => (string)$name);
        return $adapter->fetchOne($select, $bind);
    }
    /**
     * Get catalog_id
     *
     * @return string|false
     */
    public function getCatalogId()
    {
        $adapter = $this->_getReadAdapter();

        $select = $adapter->select()
            ->from(self::getMainTable(), 'catalog_id');
        return $adapter->fetchOne($select);
    }
}