<?php

class Citrus_Integration_Model_Resource_Relevant extends Mage_Core_Model_Resource_Db_Abstract
{
    public function _construct()
    {
        $this->_init('citrusintegration/relevant', 'id');
    }

    /**
     * Get catalog_id
     * @param $adId string
     * @return array
     */
    public function getIdByAdId($adId)
    {
        $host = $this->getHelper()->getHost();
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
            ->from(self::getMainTable(), 'id')
            ->where('ad_id = :adId')
            ->where('host = :host');
        $bind = array(
            ':adId' => (int)$adId,
            ':host' => (string)$host
        );
        return $adapter->fetchAll($select, $bind);
    }
    /**
     * @return Mage_Core_Helper_Abstract|Citrus_Integration_Helper_Data
     */
    protected function getHelper()
    {
        return Mage::helper('citrusintegration/data');
    }
}