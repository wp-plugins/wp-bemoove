<?php
class BeHLSApiClient {

    private $userAccountInfo;

    function __construct(UserAccountInfo $userAccountInfo){

        $this->userAccountInfo = $userAccountInfo;
    }

    static function addAcount() {
        $adminApikey = ADMIN_APIKEY;
        if (empty($adminApikey)) {
            // admin_apikeyが設定されている場合は、そのキーを利用して叩きにいく
            $dt = date("YmdHis");
            $time = time();
            $accountId = "wp_{$time}";
            $apiRootUri = self::getApiRootUriCore();
            $response = file_get_contents("{$apiRootUri}/account/add/{$accountId}/{$adminApikey}/{$dt}");
            return $this->createJsonFromHttpResponse($response);
        } else {
            // admin_apikeyが設定されていない場合は、アカウントランダム作成を行う

        }
    }

    function removeAcount() {
        $adminApikey = ADMIN_APIKEY;
        if (empty($adminApikey)) {
            // admin_apikeyが設定されている場合は、そのキーを利用して叩きにいく
            $dt = date("YmdHis");
            $response = file_get_contents("{$this->getApiRootUri()}/account/remove/{$this->userAccountInfo->getAccountId()}/{$adminApikey}/{$dt}");
            return $this->createJsonFromHttpResponse($response);
        } else {
            // admin_apikeyが設定されていない場合は、アカウントIDとアカウントAPIPREKEYを元に削除を行う

        }
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

        return self::getApiRootUriCore();
    }

    private static function getApiRootUriCore() {
        $hostName = UserAccountInfo::getBehlsHostCore();
        return "https://{$hostName}";
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