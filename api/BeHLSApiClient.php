<?php
class BeHLSApiClient {

    private $userAccountInfo;

    function __construct($userAccountInfo){

        $this->userAccountInfo = $userAccountInfo;
    }

    function getAccount() {
        $dt = date("YmdHis");
        $accountApikey = $this->getAccountApiKey($dt);
        $response = file_get_contents("{$this->getApiRootUri()}/account/get/{$this->userAccountInfo->getAccountId()}/{$accountApikey}/{$dt}");
        return $this->createJsonFromHttpResponse($response);
    }

    function listVideo() {
        $dt = date("YmdHis");
        $accountApikey = $this->getAccountApiKey($dt);
        $response = file_get_contents("{$this->getApiRootUri()}/video/list/{$this->userAccountInfo->getAccountId()}/{$accountApikey}/{$dt}");
        return $this->createJsonFromHttpResponse($response);
    }

    function getVideo($videoHash) {
        $dt = date("YmdHis");
        $accountApikey = $this->getAccountApiKey($dt);
        $response = file_get_contents("{$this->getApiRootUri()}/video/get/{$this->userAccountInfo->getAccountId()}/{$accountApikey}/{$dt}/{$videoHash}");
        return $this->createJsonFromHttpResponse($response);
    }

    function removeVideo($videoHash) {
        $dt = date("YmdHis");
        $accountApikey = $this->getAccountApiKey($dt);
        $response = file_get_contents("{$this->getApiRootUri()}/video/remove/{$this->userAccountInfo->getAccountId()}/{$accountApikey}/{$dt}/{$videoHash}");
        return $this->createJsonFromHttpResponse($response);
    }

    private function  getApiRootUri() {

        return "https://{$this->userAccountInfo->getBehlsHost()}";
    }

    private function getAccountApiKey($dt) {

        return md5($this->userAccountInfo->getAccountApiprekey() . $dt);
    }

    private function createJsonFromHttpResponse($response) {

        $xml = simplexml_load_string($response);
        return json_decode(json_encode($xml), true);
    }
}
?>