<?php
class BeHLSApiClient {

    private $subDomainName;
    private $accountId;
    private $accountApiPrekey;

    function __construct($subDomainName, $accountId, $accountApiPrekey){

        $this->subDomainName = $subDomainName;
        $this->accountId = $accountId;
        $this->accountApiPrekey = $accountApiPrekey;
    }

    function getAccount() {
        $dt = date("YmdHis");
        $accountApikey = $this->getAccountApiKey($dt);
        $response = file_get_contents("{$this->getApiRootUri()}/account/get/{$this->accountId}/{$accountApikey}/{$dt}");
        return $this->createJsonFromHttpResponse($response);
    }

    function listVideo() {
        $dt = date("YmdHis");
        $accountApikey = $this->getAccountApiKey($dt);
        $response = file_get_contents("{$this->getApiRootUri()}/video/list/{$this->accountId}/{$accountApikey}/{$dt}");
        return $this->createJsonFromHttpResponse($response);
    }

    function getVideo($videoHash) {
        $dt = date("YmdHis");
        $accountApikey = $this->getAccountApiKey($dt);
        $response = file_get_contents("{$this->getApiRootUri()}/video/get/{$this->accountId}/{$accountApikey}/{$dt}/{$videoHash}");
        return $this->createJsonFromHttpResponse($response);
    }

    function removeVideo($videoHash) {
        $dt = date("YmdHis");
        $accountApikey = $this->getAccountApiKey($dt);
        $response = file_get_contents("{$this->getApiRootUri()}/video/remove/{$this->accountId}/{$accountApikey}/{$dt}/{$videoHash}");
        return $this->createJsonFromHttpResponse($response);
    }

    private function  getApiRootUri() {

        return "http://{$this->subDomainName}.behls-lite.jp";
    }

    private function getAccountApiKey($dt) {

        return md5($this->accountApiPrekey . $dt);
    }

    private function createJsonFromHttpResponse($response) {

        $xml = simplexml_load_string($response);
        return json_decode(json_encode($xml), true);
    }
}
?>