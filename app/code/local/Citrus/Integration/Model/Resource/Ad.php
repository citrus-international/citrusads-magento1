<?php

class Citrus_Integration_Model_Resource_Ad extends Mage_Core_Model_Resource_Db_Abstract
{
    public function _construct()
    {
        $this->_init('citrusintegration/ad', 'id');
    }
    public function getIdByCitrusId($citrusId)
    {
        $host = $this->getHelper()->getHost();
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
            ->from(self::getMainTable(), 'id')
            ->where('citrus_id = :citrusId')
            ->where('host = :host');
        $bind = array(
            ':citrusId' => (string)$citrusId,
            ':host' => (string)$host
        );
        return $adapter->fetchOne($select, $bind);
    }
    public function getCitrusIdByGtin($gtin)
    {
        $host = $this->getHelper()->getHost();
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
            ->from(self::getMainTable(), 'citrus_id')
            ->where('gtin = :gtin')
            ->where('host = :host');
        $bind = array(
            ':gtin' => (string)$gtin,
            ':host' => (string)$host
        );
        return $adapter->fetchOne($select, $bind);
    }
    /**
     * @param $limit
     * @param $pageType
     * @return array
     */
    public function getAds($limit, $pageType)
    {
        $datetime = new DateTime();
        $now = $datetime->format('Y-m-d\TH:i:s\Z');
        $host = $this->getHelper()->getHost();
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
            ->from(self::getMainTable(), '*')
            ->where('host = :host')
            ->where('pageType = :pageType')
            ->where('expiry >= :expiry')->order(array('id DESC'))
            ->limit($limit);
        $bind = array(
            ':host' => (string)$host,
            ':pageType' => (string)$pageType,
            ':expiry' => $now
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