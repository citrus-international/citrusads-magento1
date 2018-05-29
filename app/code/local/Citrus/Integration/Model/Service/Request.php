<?php

class Citrus_Integration_Model_Service_Request extends Varien_Object
{
    public function requestingAnAd($body){
        $handle = 'ads/generate';
        $headers = $this->getAuthenticationModel()->getAuthorization($this->getCitrusHelper()->getApiKey());
        return self::requestPostApi($handle,$headers, $body);
    }
    /**
     * @param $body array
     * @return array
     */
    public function pushOrderRequest($body){
        $handle = 'orders';
        $headers = $this->getAuthenticationModel()->getAuthorization($this->getCitrusHelper()->getApiKey());
        $body = [
            'orders' =>
                $body
        ];
        return self::requestPostApi($handle,$headers, $body);
    }

    /**
     * @param $orderId
     * @return array|bool
     */
    public function deleteOrderRequest($orderId){
        $handle = 'orders/'.$orderId;
        $headers = $this->getAuthenticationModel()->getAuthorization($this->getCitrusHelper()->getApiKey());
        return self::requestDeleteApi($handle,$headers);
    }
    /**
     * @param $body array
     * @return array
     */
    public function pushCustomerRequest($body){
        $handle = 'customers';
        $headers = $this->getAuthenticationModel()->getAuthorization($this->getCitrusHelper()->getApiKey());
        $body = [
            'customers' =>
                $body
        ];
        return self::requestPostApi($handle,$headers, $body);
    }

    /**
     * @param $customerId
     * @return array|bool
     */
    public function deleteCustomerRequest($customerId){
        $handle = 'customers/'.$customerId;
        $headers = $this->getAuthenticationModel()->getAuthorization($this->getCitrusHelper()->getApiKey());
        return self::requestDeleteApi($handle,$headers);
    }
    /**
     * @param null $body
     * @return array
     */
    public function pushCatalogProductsRequest($body = null){
        $handle = 'catalog-products';
        $headers = $this->getAuthenticationModel()->getAuthorization($this->getCitrusHelper()->getApiKey());
        $body = [
            'catalogProducts' =>
                $body
        ];
        return self::requestPostApi($handle,$headers, $body);
    }
    /**
     * @param null $body
     * @return array
     */
    public function pushProductsRequest($body = null){
        $handle = 'products';
        $headers = $this->getAuthenticationModel()->getAuthorization($this->getCitrusHelper()->getApiKey());
        $body = [
            'products' =>
                $body
        ];
        return self::requestPostApi($handle,$headers, $body);
    }
    /**
     * @param null $name
     * @param null $id
     * @return array
     */
    public function pushCatalogsRequest($name = null, $id = null){
        $handle = 'catalogs?'.http_build_query(['teamId'=>$this->getCitrusHelper()->getTeamId()]);
        $headers = $this->getAuthenticationModel()->getAuthorization($this->getCitrusHelper()->getApiKey());
        $params['name'] = $name ? $name : 'Catalog';
        $params['teamId'] = $this->getCitrusHelper()->getTeamId();
        if($id)
            $params['id'] = $id;
        $body = [
            'catalogs' =>
                [
                    $params
                ]
        ];
        return self::requestPostApi($handle,$headers, $body);
    }
    /**
     * @param $catalogId string
     * @return array
     */
    public function deleteCatalogRequest($catalogId){
        $handle = 'catalogs/'.$catalogId;
        $headers = $this->getAuthenticationModel()->getAuthorization($this->getCitrusHelper()->getApiKey());
        return self::requestDeleteApi($handle,$headers);
    }
    /**
     * @param $gtin string
     * @param $catalogId string
     * @return array
     */
    public function deleteCatalogProductRequest($gtin, $catalogId = null){
        if($catalogId == null){
            $catalogId = $this->getCitrusHelper()->getCitrusCatalogId();
        }
        $handle = 'catalog-products/'.$catalogId.'/'.$gtin;
        $headers = $this->getAuthenticationModel()->getAuthorization($this->getCitrusHelper()->getApiKey());
        return self::requestDeleteApi($handle,$headers);
    }
    /**
     * @param $gtin string
     * @param $catalogId string
     * @return array
     */
    public function deleteProductRequest($gtin, $catalogId = null){
        if($catalogId == null){
            $catalogId = $this->getCitrusHelper()->getCitrusCatalogId();
        }
        $teamId = $this->getCitrusHelper()->getTeamId();
        $handle = 'products/gtin'.$gtin.'?teamId='.$teamId;
        $headers = $this->getAuthenticationModel()->getAuthorization($this->getCitrusHelper()->getApiKey());
        return self::requestDeleteApi($handle,$headers);
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
            $status = $curl->getInfo(CURLINFO_HTTP_CODE);
            $curl->close();
            if ($data === false) {
                return false;
            }
            if(isset($status)&& $status != 200 ){
                $result['success'] = false;
                $result['message'] = $status.'-'.$data;
            }
            else
                $result['message'] = $data;
        } catch (\Exception $exception) {
            $result['success'] = false;
            $result['message'] = $exception->getMessage();
        }
        return $result;
    }
    public function requestDeleteApi($handle, $headers = array(), $params = '')
    {
        $url = $this->getCitrusHelper()->getHost().$handle;
        $result = ['success' => true];
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $data = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($data === false) {
                return false;
            }
            if(isset($status)&& $status != 200 ){
                $result['success'] = false;
                $result['message'] = $status.'-'.$data;
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