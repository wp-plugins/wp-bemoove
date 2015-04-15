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

    /** アカウント設定が完了済か否か  */
    function hasAccount() {

        return (!empty($this->accountId) && !empty($this->accountApiprekey));
    }

    private $behlsHost;
    public function getBehlsHost() {

        if (isset($this->behlsHost)) return $this->behlsHost;

        // 設定ファイルから読み込む   
        $this->behlsHost = self::getBehlsHostCore(); 
        return $this->behlsHost;
    }

    public static function getBehlsHostCore() {

        return (BEHLS_HOST_NAME == '' ? self::DEFAULT_BEHLS_HOST : BEHLS_HOST_NAME);
    }

    private $deliveryBehlsHost;
    public function getDeliveryBehlsHost() {

        if (isset($this->deliveryBehlsHost)) return $this->deliveryBehlsHost;

        // 設定ファイルから読み込む    
        $this->deliveryBehlsHost = self::getDeliveryBehlsHostCore();
        return $this->deliveryBehlsHost;
    }

    public static function getDeliveryBehlsHostCore() {

        return (BEHLS_DELIVERY_HOST_NAME == '' ? self::getBehlsHostCore() : BEHLS_DELIVERY_HOST_NAME);
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
        return $instance;
    }

    function save() {

        $opt = array();
        $opt[self::ACCOUNT_ID_PARAM_KEY] = $this->accountId;
        $opt[self::ACCOUNT_APIPREKEY_PARAM_KEY] = $this->accountApiprekey;
        update_option(self::OPTION_KEY, $opt);
    }

    function remove() {
        $opt = array();
        $opt[self::ACCOUNT_ID_PARAM_KEY] = '';
        $opt[self::ACCOUNT_APIPREKEY_PARAM_KEY] = '';
        $this->accountId = '';
        $this->accountApiprekey = '';
        update_option(self::OPTION_KEY, $opt);
        update_option(self::OPTION_KEY, $opt);
    }
}
?>
