<?php

include_once __DIR__.'/../../vendor/autoload.php';

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

class Citrus_Integration_Model_Service_Request extends Varien_Object
{

    private $guzzleClient;
    const DEFAULT_TIMEOUT_SECONDS = 5;
    const DEFAULT_GENERATE_AD_TIMEOUT_SECONDS = 1;

    public function _construct()
    {
        parent::_construct();
        $this->initGuzzle();
    }

    private function initGuzzle() {

        $this->guzzleClient = new Client([
//            // Base URI is used with relative requests
//            'base_uri' => self::BASE_URI,
            // You can set any number of default request options.
            'timeout'  => self::DEFAULT_TIMEOUT_SECONDS,
        ]);
    }

    public function requestingAnAd($body)
    {
        $handle = 'ads/generate';
        $headers = $this->getAuthenticationModel()->getAuthorizationGuzzle($this->getCitrusHelper()->getApiKey());
        $timeOut = floatval(Mage::getStoreConfig('citrus_sync/citrus_ads/time_out'));
        if ($timeOut <= 0) {
            $timeOut = self::DEFAULT_GENERATE_AD_TIMEOUT_SECONDS;
        }
        // error_log("time out is : " . $timeOut . PHP_EOL);
        return self::requestPostApi($handle, $headers, $body, $timeOut);
    }
    /**
     * @param $body array
     * @return array
     */
    public function pushOrderRequest($body)
    {
        $handle = 'orders';
        $headers = $this->getAuthenticationModel()->getAuthorizationGuzzle($this->getCitrusHelper()->getApiKey());
        $body = array(
            'orders' =>
                $body
        );
        return self::requestPostApi($handle, $headers, $body);
    }

    /**
     * @param $orderId
     * @return array|bool
     */
    public function deleteOrderRequest($orderId)
    {
        $handle = 'orders/'.$orderId;
        $headers = $this->getAuthenticationModel()->getAuthorizationGuzzle($this->getCitrusHelper()->getApiKey());
        return self::requestDeleteApi($handle, $headers);
    }
    /**
     * @param $body array
     * @return array
     */
    public function pushCustomerRequest($body)
    {
        $handle = 'customers';
        $headers = $this->getAuthenticationModel()->getAuthorizationGuzzle($this->getCitrusHelper()->getApiKey());
        $body = array(
            'customers' =>
                $body
        );
        return self::requestPostApi($handle, $headers, $body);
    }

    /**
     * @param $customerId
     * @return array|bool
     */
    public function deleteCustomerRequest($customerId)
    {
        $handle = 'customers/'.$customerId;
        $headers = $this->getAuthenticationModel()->getAuthorizationGuzzle($this->getCitrusHelper()->getApiKey());
        return self::requestDeleteApi($handle, $headers);
    }
    /**
     * @param null $body
     * @return array
     */
    public function pushCatalogProductsRequest($body = null)
    {
        $handle = 'catalog-products';
        $headers = $this->getAuthenticationModel()->getAuthorizationGuzzle($this->getCitrusHelper()->getApiKey());
        $requestBody = array(
            'catalogProducts' =>
                $body
        );
//        return self::requestPostApi($handle, $headers, $requestBody);
        return self::requestPostApi($handle, $headers, $requestBody);
    }
    /**
     * @param null $body
     * @return array
     */
    public function pushProductsRequest($body = null)
    {
        $handle = 'products';
        $headers = $this->getAuthenticationModel()->getAuthorizationGuzzle($this->getCitrusHelper()->getApiKey());
        $body = array(
            'products' =>
                $body
        );
        return self::requestPostApi($handle, $headers, $body);
    }
    /**
     * @param null $name
     * @param null $id
     * @return array
     */
    public function pushCatalogsRequest($name = null, $id = null)
    {
        $handle = 'catalogs?'.http_build_query(array('teamId'=>$this->getCitrusHelper()->getTeamId()));
        $headers = $this->getAuthenticationModel()->getAuthorizationGuzzle($this->getCitrusHelper()->getApiKey());
        $params['name'] = $name ? $name : 'Catalog';
        $params['teamId'] = $this->getCitrusHelper()->getTeamId();
        if($id)
            $params['id'] = $id;
        $body = array(
            'catalogs' =>
                array(
                    $params
                )
        );
        return self::requestPostApi($handle, $headers, $body);
    }
    /**
     * @param $catalogId string
     * @return array
     */
    public function deleteCatalogRequest($catalogId)
    {
        $handle = 'catalogs/'.$catalogId;
        $headers = $this->getAuthenticationModel()->getAuthorizationGuzzle($this->getCitrusHelper()->getApiKey());
        return self::requestDeleteApi($handle, $headers);
    }
    /**
     * @param $gtin string
     * @param $catalogId string
     * @return array
     */
    public function deleteCatalogProductRequest($gtin, $catalogId = null)
    {
        if($catalogId == null){
            $catalogId = $this->getCitrusHelper()->getCitrusCatalogId();
        }

        $handle = 'catalog-products/'.$catalogId.'/'.$gtin;
        $headers = $this->getAuthenticationModel()->getAuthorizationGuzzle($this->getCitrusHelper()->getApiKey());
        return self::requestDeleteApi($handle, $headers);
    }
    /**
     * @param $gtin string
     * @param $catalogId string
     * @return array
     */
    public function deleteProductRequest($gtin, $catalogId = null)
    {
        if($catalogId == null){
            $catalogId = $this->getCitrusHelper()->getCitrusCatalogId();
        }

        $teamId = $this->getCitrusHelper()->getTeamId();
        $handle = 'products/'.$gtin.'?teamId='.$teamId;
        $headers = $this->getAuthenticationModel()->getAuthorizationGuzzle($this->getCitrusHelper()->getApiKey());
        return self::requestDeleteApi($handle, $headers);
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
    public function requestPostApiOld($handle, $headers = array(), $params = '')
    {
        $url = $this->getCitrusHelper()->getHost().$handle;
        $result = array('success' => true);
        $body = json_encode($params);
        try {
            $curl = new Varien_Http_Adapter_Curl();
            $curl->write(Zend_Http_Client::POST, $url, '1.1', $headers, $body);
            $data = $curl->read();
            $data = preg_split('/^\r\n/m', $data);
            $data = trim($data[1]);
            $status = $curl->getInfo(CURLINFO_HTTP_CODE);
            $curl->close();
            if ($data === false) {
                return false;
            }

            if(isset($status)&& $status != 200){
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

    public function requestPostApi($handle, $headers=array(), $requestBody=array(), $timeout=self::DEFAULT_TIMEOUT_SECONDS) {

        $url = $this->getCitrusHelper()->getHost().$handle;
        $options = array(
            "headers" => $headers,
            "body" => json_encode($requestBody),
            "version" => '1.1',
            "timeout" => $timeout
        );

        $result = array();
        try {
            $promise = $this->guzzleClient->requestAsync('POST', $url, $options);
            $promise->then(
                function (ResponseInterface $res) use (&$result) {
                    $result['success'] = true;
                    $result['message'] = $res->getBody()->getContents();
                },
                function (RequestException $e) use (&$result) {
                    $result['success'] = false;
                    $result['message'] = $e->getMessage();
                }
            );
            $promise->wait();
            // error_log("finished waiting POST!");
        } catch (Exception $e) {
            $result['success'] = false;
            $result['message'] = $e->getMessage();
            error_log("Exception while doing POST to " . $url . ". Error Message: " . $e->getMessage());
        }
        return $result;
    }

    public function requestDeleteApiOld($handle, $headers = array(), $params = '')
    {
        $url = $this->getCitrusHelper()->getHost().$handle;
        $result = array('success' => true);
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

            if(isset($status)&& $status != 200){
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

    public function requestDeleteApi($handle, $headers=array()) {

        $url = $this->getCitrusHelper()->getHost().$handle;
        $options = array(
            "headers" => $headers,
            "version" => '1.1'
        );

        $result = array();
        try {
            $promise = $this->guzzleClient->requestAsync('DELETE', $url, $options);
            $promise->then(
                function (ResponseInterface $res) use (&$result) {
                    $result['success'] = true;
                    $result['message'] = $res->getBody()->getContents();
                },
                function (RequestException $e) use (&$result) {
                    $result['success'] = false;
                    $result['message'] = $e->getMessage();
                }
            );
            $promise->wait();
            // error_log("Finished waiting DELETE");
        } catch (Exception $e) {
            $result['success'] = false;
            $result['message'] = $e->getMessage();
            error_log("Exception while doing DELETE to " . $url . ". Error Message: " . $e->getMessage());
        }
        return $result;
    }
}