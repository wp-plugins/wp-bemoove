<?php
class BeHLSApiClient {

    private $userAccountInfo;

    function __construct(UserAccountInfo $userAccountInfo){

        $this->userAccountInfo = $userAccountInfo;
    }

    static function addAcount() {
        $adminApiPrekey = ADMIN_APIPREKEY;
        if (!empty($adminApiPrekey)) {
            // admin_apikeyが設定されている場合は、そのキーを利用して叩きにいく
            $time = time();
            $accountId = "wp_{$time}";
            $apiRootUri = self::getApiRootUriCore();
            $dt = date("YmdHis");
            $adminApikey = self::createKey($adminApiPrekey, $dt);
            $response = file_get_contents("{$apiRootUri}/account/add/{$accountId}/{$adminApikey}/{$dt}/");
            return self::createJsonFromHttpResponseCore($response);
        } else {
            // admin_apikeyが設定されていない場合は、アカウントランダム作成を行う
            $apiRootUri = self::getAdminApiRootUriCore();
            // ip-address user-agent を転送
            $ipAddress = $_SERVER["REMOTE_ADDR"];
            $userAgent = $_SERVER["HTTP_USER_AGENT"];

            $header = Array(
                "User-Agent: {$userAgent}"
                , "X-Forwarded-For: {$ipAddress}"
            );
            $context = stream_context_create(array('http' => array(
                'method' => 'GET'
                , 'header' => implode("\r\n", $header)
            )));
            $response = file_get_contents("{$apiRootUri}/account/add.php", false, $context);
            return self::createJsonFromHttpResponseCore($response);
        }
    }

    function removeAcount() {
        $adminApiPrekey = ADMIN_APIPREKEY;
        $dt = date("YmdHis");
        if (!empty($adminApiPrekey)) {
            // admin_apikeyが設定されている場合は、そのキーを利用して叩きにいく
            $adminApikey = self::createKey($adminApiPrekey, $dt);
            $response = file_get_contents("{$this->getApiRootUri()}/account/remove/{$this->userAccountInfo->getAccountId()}/{$adminApikey}/{$dt}");
            return $this->createJsonFromHttpResponse($response);
        } else {
            // admin_apikeyが設定されていない場合は、アカウントIDとアカウントAPIPREKEYを元に削除を行う
            $apiRootUri = self::getAdminApiRootUriCore();
            $accountApikey = $this->getAccountApiKey($dt);
            $response = file_get_contents("{$apiRootUri}/account/remove.php?id={$this->userAccountInfo->getAccountId()}&apikey={$accountApikey}&dt={$dt}");
            return $this->createJsonFromHttpResponse($response);
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
        return BEHLS_PROTOCOL . "://{$hostName}";
    }

    private static function getAdminApiRootUriCore() {

        return BEHLS_PROTOCOL . "://" . BEHLS_ADMIN_PROXY_HOST_NAME;
    }

    private function getAccountApiKey($dt) {

        return self::createKey($this->userAccountInfo->getAccountApiprekey(), $dt);
    }

    private static function createKey($key, $dt) {

        return md5($key . $dt);
    }

    private function createJsonFromHttpResponse($response) {

        return self::createJsonFromHttpResponseCore($response);
    }

    private function createJsonFromHttpResponseCore($response) {

        $xml = simplexml_load_string($response);
        return json_decode(json_encode($xml), true);
    }
}
?>