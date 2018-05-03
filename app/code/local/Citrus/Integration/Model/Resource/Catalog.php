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
        $host = $this->getHelper()->getHost();
        $adapter = $this->_getReadAdapter();
        $teamId = $this->getHelper()->getTeamId();
        $select = $adapter->select()
            ->from(self::getMainTable(), 'catalog_id')
            ->where('name = :name')
            ->where('host = :host')
            ->where('teamId = :teamId');
        $bind = array(
            ':name' => (string)$name,
            ':host' => (string)$host,
            ':teamId' => (string)$teamId
        );
        return $adapter->fetchOne($select, $bind);
    }
    /**
     * Get catalog_id
     * @param $name string
     * @return string|false
     */
    public function getIdByName($name)
    {
        $host = $this->getHelper()->getHost();
        $teamId = $this->getHelper()->getTeamId();
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
            ->from(self::getMainTable(), 'id')
            ->where('name = :name')
            ->where('host = :host')
            ->where('teamId = :teamId');
        $bind = array(
            ':name' => (string)$name,
            ':host' => (string)$host,
            ':teamId' => (string)$teamId
        );
        return $adapter->fetchOne($select, $bind);
    }
    /**
     * @return Mage_Core_Helper_Abstract|Citrus_Integration_Helper_Data
     */
    protected function getHelper(){
        return Mage::helper('citrusintegration/data');
    }
}