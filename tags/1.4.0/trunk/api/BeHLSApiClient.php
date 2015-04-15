<?php
global $wpdb;
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
            //get site url and get its host => domain
            $bm_domain_url = self::getbm_SubDomain_acct_dm();
            $parse = parse_url($bm_domain_url);
            $bm_domain=$parse['host'];    //<-- its value is the domain       

            $header = Array(
                "User-Agent: {$userAgent}"
                , "X-Forwarded-For: {$ipAddress}"
            );
            $context = stream_context_create(array('http' => array(
                'method' => 'GET',
                'header' => implode("\r\n", $header)
            )));
            $response = file_get_contents("{$apiRootUri}/account/add.php?domain=".$bm_domain, true, $context);
            if(!$response){ echo "<script>window.location.href='?page=BeMoOve_welcome&error=1';</script>"; }
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

    function getAccount($use_domain) {
        
        $dt = date("YmdHis");
        $accountApikey = $this->getAccountApiKey($dt);
        $mbms_domain = $use_domain; 
        $bmch=0;
        if($use_domain==''){ 
            $use_domain= $this->getbm_SubDomain_acct();
            $bmch=1; 
        } 
        if($use_domain!='wordpress'){  //標準のアカウントを持っているかどうかを確認しようとします
            $uriSbm = explode('//',$this->getApiRootUri());
            $use_domain2=$uriSbm[0].'//'.$use_domain.'.behls.jp';
            $response = file_get_contents("{$use_domain2}/account/get/{$this->userAccountInfo->getAccountId()}/{$accountApikey}/{$dt}");
            $bm_res = json_encode($response);

            if (strstr($bm_res, "Invalid id or apikey")){  //  liteのアカウントを進める
                $uriSbm = explode('//',$this->getApiRootUri());
                $use_domain2=$uriSbm[0].'//'.$use_domain.'.behls-lite.jp';
                $response = file_get_contents("{$use_domain2}/account/get/{$this->userAccountInfo->getAccountId()}/{$accountApikey}/{$dt}");
                $bm_res = json_encode($response);
            }

        }else{ 
            $bm_res = "Invalid id or apikey";
            
        }
        if (strstr($bm_res, "Invalid id or apikey")){
            $response = file_get_contents("{$this->getApiRootUri()}/account/get/{$this->userAccountInfo->getAccountId()}/{$accountApikey}/{$dt}"); 
            if($bmch==0 && $mbms_domain!='wordpress'){ $use_domain='wordpress'; echo '<script>alert(\'Sorry, your subdomain "'.$mbms_domain.'" is invalid, so we use the default subdomain!\');</script>'; }
            $this->addbm_SubDomain_acct($use_domain,'Lite');
            return $this->createJsonFromHttpResponse($response); 

            }
        else{ 
            $this->addbm_SubDomain_acct($mbms_domain,'Standard');
            return $this->createJsonFromHttpResponse($response);
            }
    }

    function addbm_SubDomain_acct($inf,$acct_t){ //<<----- added for v 1.4.0
            global $wpdb; 
            $table_name = $wpdb->prefix . 'settings_meta_bm'; 
            $sql="UPDATE $table_name SET acctount_type = '".$inf."', account_status = '".$acct_t."' WHERE setting_id=1";
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql ); 
    }
    function getbm_SubDomain_acct(){ //<<----- added for v 1.4.0
            $inf=1;
            $rVal='';
            global $wpdb;
            $table_name = $wpdb->prefix . 'settings_meta_bm'; 
            require_once( BM_CG_WP_DIR2 . 'wp-admin/includes/upgrade.php' );
            $row = $wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$table_name." WHERE setting_id='%d'", $inf) );
            foreach($row as $key => $value) {
              if($key=='acctount_type'){ $rVal = $value; }
            }
            return $rVal;
    }
    function getbm_SubDomain_acct_dm(){ //<<----- added for v 1.4.0
            $inf=1;
            $rVal='';
            global $wpdb;
            $table_name = $wpdb->prefix . 'settings_meta_bm'; 
            require_once( BM_CG_WP_DIR2 . 'wp-admin/includes/upgrade.php' );
            $row = $wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$table_name." WHERE setting_id='%d'", $inf) );
            foreach($row as $key => $value) {
              if($key=='site_url'){ $rVal = $value; }
            }
            return $rVal;
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
        return PROTOCOL . "://{$hostName}";
    }

    private static function getAdminApiRootUriCore() {

        return PROTOCOL . "://" . BEHLS_ADMIN_PROXY_HOST_NAME;
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