<?php

class Citrus_Integration_Model_Service_Request extends Varien_Object
{
    public function pushCatalogProductsRequest($body = null, $catalogId = null){
        $handle = 'catalog-products';
        $headers = $this->getAuthenticationModel()->getAuthorization($this->getCitrusHelper()->getApiKey());
        $body = [
            'catalogProducts' =>
                [$body]
        ];
        return self::requestPostApi($handle,$headers, $body);
    }
    /**
     * @param null $name
     * @return array
     */
    public function pushCatalogsRequest($name = null){
        $handle = 'catalogs?'.http_build_query(['teamId'=>$this->getCitrusHelper()->getTeamId()]);
        $headers = $this->getAuthenticationModel()->getAuthorization($this->getCitrusHelper()->getApiKey());
        $params['name'] = $name ? $name : 'Catalog';
        $params['teamId'] = $this->getCitrusHelper()->getTeamId();
        $body = [
            'catalogs' =>
                [
                    $params
                ]
        ];
        return self::requestPostApi($handle,$headers, $body);
    }
    /**
     * @return false|Citrus_Integration_Model_Service_Authentication
     */
    protected function getAuthenticationModel(){
        return Mage::getModel('citrusintegration/service_authentication');
    }
    /**
     * @return false|Citrus_Integration_Model_Catalog
     */
    protected function getCatalogModel(){
        return Mage::getModel('citrusintegration/catalog');
    }
    /**
     * @return false|Citrus_Integration_Helper_Data
     */
    protected function getCitrusHelper(){
        return Mage::helper('citrusintegration/data');
    }
    /**
     * @param string $handle
     * @param array|null $headers
     * @param string $params
     * @return array
     */
    public function requestPostApi($handle, $headers = array(), $params = '')
    {
        $url = $this->getCitrusHelper()->getHost().$handle;
        $result = ['success' => true];
        $body = json_encode($params);
        try {
            $curl = new Varien_Http_Adapter_Curl();
            $curl->write(Zend_Http_Client::POST, $url , '1.1', $headers, $body);
            $data = $curl->read();
            $data = preg_split('/^\r\n/m', $data);
            $data = trim($data[1]);
            $status = $curl->getInfo();
            $curl->close();
            if ($data === false) {
                return false;
            }
            if(isset($status['http_code'])&& $status['http_code'] != 200 ){
                $result['success'] = false;
                $result['message'] = $data;
            }
            else
                $result['message'] = $data;
        } catch (\Exception $exception) {
            $result['success'] = false;
            $result['message'] = $exception->getMessage();
        }

        return $result;
    }
}