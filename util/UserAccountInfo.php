<?php
class UserAccountInfo {

    const DEFAULT_BEHLS_HOST = "wordpress.behls-lite.jp";
    const OPTION_KEY = "BeMoOve_admin_datas";
    const ACCOUNT_ID_PARAM_KEY = "account_id";
    const ACCOUNT_APIPREKEY_PARAM_KEY = "account_apiprekey";

    private $accountId;

    function getAccountId() {

        return $this->accountId;
    }

    private $accountApiprekey;

    function getAccountApiprekey() {

        return $this->accountApiprekey;
    }

    private $behlsHost;
    function getBehlsHost() {

        if (isset($this->behlsHost)) return $this->behlsHost;

        // 設定ファイルから読み込む
        $this->behlsHost = (BEHLS_HOST_NAME == '' ? self::DEFAULT_BEHLS_HOST : BEHLS_HOST_NAME);
        return $this->behlsHost;
    }

    private function __construct($accountId, $accountApiprekey){

        $this->accountId = $accountId;
        $this->accountApiprekey = $accountApiprekey;
    }

    static function getInstance() {
        $opt = get_option(self::OPTION_KEY);

        return new UserAccountInfo(
            $opt[self::ACCOUNT_ID_PARAM_KEY]
            , $opt[self::ACCOUNT_APIPREKEY_PARAM_KEY]
        );
    }

    static function createInstance($accountId, $accountApiprekey) {

        $instance = new UserAccountInfo($accountId, $accountApiprekey);
        $instance->save();
        return $instance;
    }

    private function save() {

        $opt = array();
        $opt[self::ACCOUNT_ID_PARAM_KEY] = $this->accountId;
        $opt[self::ACCOUNT_APIPREKEY_PARAM_KEY] = $this->accountApiprekey;
        update_option(self::OPTION_KEY, $opt);
    }
}
?>