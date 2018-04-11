<?php

class Citrus_Integration_Helper_Api extends Mage_Core_Helper_Data
{
    const CITRUS_STAGING_SERVER = "https://staging-integration.citrusad.com/v1/";
    const CITRUS_AU_SERVER = "https://au-integration.citrusad.com/v1/";
    const CITRUS_US_SERVER = "https://us-integration.citrusad.com/v1/";

    /**
     * @param string $handle
     * @param array|null $headers
     * @param string $params
     * @return array
     */
    public static function requestPostApi($handle, $headers = array(), $params = '')
    {
        $url = self::CITRUS_STAGING_SERVER . $handle;
        $result = ['success' => true];
        $body = json_encode($params);
        try {
            $curl = new Varien_Http_Adapter_Curl();
            $curl->write(Zend_Http_Client::POST, $url , '1.1', $headers, $body);
            $data = $curl->read();
            if ($data === false) {
                return false;
            }
            $data = preg_split('/^\r\n/m', $data);
            $data = trim($data[1]);
            $curl->close();
            $result['message'] = $data;
        } catch (\Exception $exception) {
            $result['success'] = false;
            $result['message'] = $exception->getMessage();
        }

        return $result;
    }

    /**
     * @param array $params
     * @param string|null $name
     * @param array|null $apiKey
     * @return array
     */
    public static function setCatalog($params, $apiKey = null, $name = null)
    {
        $url = 'catalogs?';
        if (is_array($params))
            $url .= http_build_query($params);
        $headers = [];
        if($apiKey)
            $headers = [
                'accept: application/json',
                'content-type: application/json',
                'Authorization: Basic '.$apiKey
            ];
        $params['name'] = $name ? $name : 'Catalog';
        $body = [
            'catalogs' =>
                [
                    $params
                ]
        ];
        return self::requestPostApi($url,$headers, $body);
    }

}