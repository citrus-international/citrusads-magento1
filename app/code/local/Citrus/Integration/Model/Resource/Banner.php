<?php

class Citrus_Integration_Model_Resource_Banner extends Mage_Core_Model_Resource_Db_Abstract
{
    public function _construct()
    {
        $this->_init('citrusintegration/banner', 'id');
    }

    /**
     * Get catalog_id
     * @param $bannerId string
     * @return string
     */
    public function getIdByCitrusId($bannerId)
    {
        $datetime = new DateTime();
        $now = $datetime->format('Y-m-d\TH:i:s\Z');
        $host = $this->getHelper()->getHost();
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
            ->from(self::getMainTable(), 'id')
            ->where('slotId = :slotId')
            ->where('host = :host')
            ->where('expiry >= :expiry');
        $bind = array(
            ':slotId' => (int)$bannerId,
            ':host' => (string)$host,
            ':expiry' => $now
        );
        return $adapter->fetchOne($select, $bind);
    }

    /**
     * @param $pageType
     * @return array
     */
    public function getBannerByPageType($pageType)
    {
        $datetime = new DateTime();
        $limit = Mage::getStoreConfig('citrus/citrus_banner/'.strtolower($pageType).'_limit', Mage::app()->getStore());
        $now = $datetime->format('Y-m-d\TH:i:s\Z');
        $host = $this->getHelper()->getHost();
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
            ->from(self::getMainTable(), 'id')
            ->where('pageType = :pageType')
            ->where('host = :host')
            ->where('expiry >= :expiry');
        if($limit) $select = $select->limit((int)$limit);
        $bind = array(
            ':pageType' => (string)$pageType,
            ':host' => (string)$host,
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