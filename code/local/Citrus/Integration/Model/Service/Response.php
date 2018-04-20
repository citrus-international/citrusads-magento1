<?php

class Citrus_Integration_Model_Service_Response extends Varien_Object
{
    /**
     * @param string $catalogId
     * @param null|array $gtin
     * @param null|int $limit
     * @param null|int $skip
     * @return array
     */
    public function getCatalogProductsListResponse($catalogId, $gtin = null, $limit = null, $skip = null)
    {
        $headers = $this->getAuthenticationModel()->getAuthorization($this->getCitrusHelper()->getApiKey());
        $body['catalogId'] = $catalogId;
        if ($gtin) $body['gtin'] = $gtin;
        if ($limit) $body['limit'] = $limit;
        if ($skip) $body['skip'] = $skip;
        $handle = 'catalog-products/3898addf-69eb-4631-812b-f0b952145563/2';
        return self::requestGetApi($handle, $headers, $body);
    }

    /**
     * @return array
     */
    public function getCatalogListResponse()
    {
        $headers = $this->getAuthenticationModel()->getAuthorization($this->getCitrusHelper()->getApiKey());
        $teamId = $this->getCitrusHelper()->getTeamId();
        $data = [
            'teamId' => $teamId
        ];
        $handle = 'catalogs?'.http_build_query($data);
        return self::requestGetApi($handle, $headers);
    }

    /**
     * @return false|Citrus_Integration_Model_Service_Authentication
     */
    protected function getAuthenticationModel()
    {
        return Mage::getModel('citrusintegration/service_authentication');
    }

    /**
     * @return false|Citrus_Integration_Model_Catalog
     */
    protected function getCatalogModel()
    {
        return Mage::getModel('citrusintegration/catalog');
    }

    /**
     * @return false|Citrus_Integration_Helper_Data
     */
    protected function getCitrusHelper()
    {
        return Mage::helper('citrusintegration/data');
    }

    /**
     * @param string $handle
     * @param array|null $headers
     * @param string $params
     * @return array
     */
    public function requestGetApi($handle, $headers = array(), $params = '')
    {
        $url = $this->getCitrusHelper()->getHost() . $handle;
        $result = ['success' => true];
        try {
            $curl = new Varien_Http_Adapter_Curl();
            $curl->write(Zend_Http_Client::GET, $url, '1.1', $headers);
            $data = $curl->read();
            $data = preg_split('/^\r\n/m', $data);
            $data = trim($data[1]);
            $status = $curl->getInfo(CURLINFO_HTTP_CODE);
            $curl->close();
            if ($data === false) {
                return false;
            }
            if (isset($status) && $status != 200) {
                $result['success'] = false;
                $result['message'] = $data;
            } else
                $result['message'] = $data;
        } catch (\Exception $exception) {
            $result['success'] = false;
            $result['message'] = $exception->getMessage();
        }

        return $result;
    }
}