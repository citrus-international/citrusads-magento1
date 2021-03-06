<?php

class Citrus_Integration_Model_Service_Authentication extends Varien_Object
{
    const CITRUS_STAGING_SERVER = "https://staging-integration.citrusad.com/v1/";
    const CITRUS_AU_SERVER = "https://au-integration.citrusad.com/v1/";
    const CITRUS_US_SERVER = "https://us-west-2-integration.citrusad.com/v1/";

    public function getAuthorization($apiKey)
    {
        $headers = array(
            'accept: application/json',
            'content-type: application/json',
            'Authorization: Basic '.$apiKey
        );
        return $headers;
    }

    public function getAuthorizationGuzzle($apiKey)
    {
        $headers = array(
            'Accept' => 'application/json',
            'content-type' => 'application/json',
            'Authorization' => 'Basic '.$apiKey
        );
        return $headers;
    }
}
