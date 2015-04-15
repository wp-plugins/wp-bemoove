<?php
require_once(WP_BeMoOve__PLUGIN_DIR . 'util/Rect.php');
require_once(WP_BeMoOve__PLUGIN_DIR . 'data/WPMovieMetaDataAdapter.php');
require_once(WP_BeMoOve__PLUGIN_DIR . 'api/BeHLSApiClient.php');
require_once(WP_BeMoOve__PLUGIN_DIR . 'bm_cgvidposts.php');  
date_default_timezone_set('Asia/Tokyo');
$bm_regKeyMsg = 0;

class BeMoOve_Admin_Class {

    private $wPMovieMetaDataAdapter;
    private function  getWPMovieMetaDataAdapter() {

        return $this->wPMovieMetaDataAdapter;
    } 

    private $userAccountInfo;

    private function getUserAccountInfo() {
        if (isset($this->userAccountInfo)) return $this->userAccountInfo;

        $this->userAccountInfo = UserAccountInfo::getInstance();
        return $this->userAccountInfo;
    } 


    private function setUserAccountInfo(UserAccountInfo $userAccountInfo) {

        $this->userAccountInfo = $userAccountInfo;
    }

    function __construct() {

        $this->wPMovieMetaDataAdapter = new WPMovieMetaDataAdapter();

        // plugin有効化時の処理を設定
        register_activation_hook (__FILE__, self::cmt_activate($this->wPMovieMetaDataAdapter));

        // カスタムフィールドの作成
        add_action('admin_menu', array($this, 'add_pages'));
        add_action('admin_head', array($this, 'wp_custom_admin_css'), 100);
        add_action('admin_head', array($this, 'includeAdminJs'), 100);
        if ($this->getUserAccountInfo()->hasAccount()) {
            // 動画一覧のajax通信用のアクション登録
            add_action('wp_ajax_get_bemoove_movie_listitem_Info', array($this, 'get_bemoove_movie_listitem_Info'));
        }
    }

    function add_pages() {

        if ($this->getUserAccountInfo()->hasAccount()) {
            add_menu_page('BeMoOve','BeMoOve', 'level_8', 'BeMoOve_movies_list', array($this, 'BeMoOve_Movies_List_Page'), '', NULL);
            add_submenu_page('BeMoOve_movies_list', '新規追加', '新規追加', 'level_8', 'BeMoOve_new', array($this, 'BeMoOve_Input_Page'));   //J->  新規追加 , 新規追加
            add_submenu_page('BeMoOve_movies_list', 'MRSS', 'MRSS', 'level_8', 'BeMoOve_vpost', array($this, 'BeMoOve_Video_Posts'));   // <================  V1.4.0のために追加 -  動画投稿の一覧 
            add_submenu_page('BeMoOve_movies_list', 'アカウント設定', 'アカウント設定', 'level_8', 'BeMoOve_setting', array($this, 'BeMoOve_Admin_Page')); //J-> アカウント設定
            add_submenu_page('BeMoOve_movies_list', '使い方', '使い方', 'level_8', 'BeMoOve_help', array($this, 'BeMoOve_Help_Page')); //J-> 使い方
            add_submenu_page('BeMoOve_movies_list', '<a href="http://www.bemoove.jp/contact/" style="padding-top:0px; margin-top:0px;" target="_blank">お問い合わせ</a>', '<a href="http://www.bemoove.jp/contact/" style="padding-top:0px; margin-top:0px;" target="_blank">お問い合わせ</a>', 'level_8', 'BeMoOve_Contact', array($this, 'BeMoOve_Contact_Page')); //<--  added for v 1.4.0  お問い合わせ - Contact Us       
        } else {
            add_menu_page('BeMoOve','BeMoOve', 'level_8', 'BeMoOve_welcome', array($this, 'BeMoOve_Welcome_Page'), '', NULL);
        }
    } 
function BeMoOve_Contact_Page(){
    echo "<script>
            window.history.back();
          </script>";
    }
    function BeMoOve_Video_Posts(){ // <================   含まれるビデオ投稿ページ/インターフェイス - V1.4.0のために追加
        include_once('bm_vmod.php');
    } 

    function wp_custom_admin_css() {
        $url = WP_BeMoOve__PLUGIN_URL . 'css/style.css';
        print('<link rel = "stylesheet" href = "'.$url.'" type="text/css" media="all" />');
    }

    function includeAdminJs() {
        $jsRoot = WP_BeMoOve__PLUGIN_URL . 'js';
        print('<script src="' . $jsRoot . '/jquery-1.11.1.min.js"></script>'
            . '<script src="' . $jsRoot . '/admin.js" type="text/javascript"></script>');
    }

    function BeMoOve_Welcome_Page() {

        // アカウント登録か否かを判断する。
        $isAccountDeleted = $_GET['deleted'] == '1';
        $isRestartAccount = $_POST['restart_account'] == '1';
        $errOnRestartAccount = null;
        $isRegisterAccount = $_POST['register_account'] == '1';
        $isRestartPage = $isRestartAccount || $_GET['restart'] == '1';
        $isAccountRegisterCompleted = false;

        if ($isRestartAccount) {
        	$bm_regKeyMsg=1;
            // アカウント再利用登録時
            $accountId = $_POST['account_id'];
            $accountApiprekey = $_POST['account_apiprekey'];

            if (empty($accountId)) {
                $errOnRestartAccount .= 'account_idは必須入力項目です。 <br />'; 
            }
            if (empty($accountApiprekey)) {
                $errOnRestartAccount .= 'account_apiprekeyは必須入力項目です。<br />'; 
            }

            if (empty($errOnRestartAccount)) {
                $userAccountInfo = UserAccountInfo::createInstance($accountId, $accountApiprekey);
                
                $apiClient = new BeHLSApiClient($userAccountInfo);
                $accountdata = $apiClient->getAccount($bmSub_wpdomain);  //<--------------- V 1.4用の更新プログラム
                if ($accountdata && $accountdata[getAccount][item][activate] == 'T') {
                    // アカウント情報が正しい場合
                    $isAccountRegisterCompleted = true;
                    $userAccountInfo->save();
                    $this->setUserAccountInfo($userAccountInfo);
                    $this->syncAccountData();
                } else {
                    $errOnRestartAccount .= "入力情報に誤りがあります。 <br />"; //<--------------- V 1.4用の更新プログラム
                }
            }
        } elseif ($isRegisterAccount) {
            // アカウント新規作成時
            $accountdata = BeHLSApiClient::addAcount();
            
            if ($accountdata && $accountdata[addAccount][item][activate] == 'T') {
                // アカウント情報が正しい場合
                $isAccountRegisterCompleted = true;
                $userAccountInfo = UserAccountInfo::createInstance(
                    $accountdata[addAccount][item][id]
                    , $accountdata[addAccount][item][prekey]
                );
                $userAccountInfo->save();
                $this->setUserAccountInfo($userAccountInfo);
                $this->syncAccountData();

            }else{
            	echo "<script>window.location.href='?page=BeMoOve_welcome&error=1';</script>";
            }
        }

        if ($isAccountDeleted) {
            // アカウントが削除された場合 
?>
<div class="wrap welcome_area">
    <h2>アカウント設定</h2> 
    <div class="info">
        アカウントの削除が完了しました。 
    </div>
    <div class="navi_area">
        <h4>■新しくアカウントを登録してご利用いただく場合はこちら</h4>
        <div><a href="admin.php?page=BeMoOve_welcome" class="link_btn">新しくアカウントを登録する</a></div> 
    </div>
</div>
<?php
        } elseif ($isAccountRegisterCompleted) {
            // アカウント登録（再利用含め）が正常に完了した場合 
            if ($isRestartAccount) {
                // 結果ページへリダイレクト    
                $fade_msg = 'アカウントの設定が完了しました。'; 
                $location = admin_url() . 'admin.php?page=BeMoOve_movies_list&restart_account=1';
                die("<script type=\"text/javascript\">(function() { location.href = \"{$location}\"; })();</script>"); 
            }
?>
<div class="wrap welcome_area">
    <h2>利用開始準備完了</h2>
    <div class="info">
        WP-BemoovePluginのご利用登録が完了しました。  
    </div>
    <div class="navi_area">
        <h4>■ さっそくWP-BemoovePluginをご利用いただく場合はこちら.</h4> 
        <div><a href="admin.php?page=BeMoOve_new" class="link_btn">動画をアップロードする</a></div> 
    </div>
    <div class="navi_area">
        <h4>■ 使い方を確認いただく場合はこちら</h4> 
        <div><a href="admin.php?page=BeMoOve_help" class="link_btn">プラグインの使い方</a></div> 
    </div>
</div>
<?php
        } elseif ($isRestartPage) {
            // アカウント再利用ページの場合 
?>
<div class="wrap welcome_area">
    <h2>利用開始準備<a href="admin.php?page=BeMoOve_welcome" class="add-new-h2">戻る</a></h2> 
    <div class="info">
        前回WP-BemoovePluginをご利用いただいた際のアカウント情報を入力してください。<br />
        <?php print(empty($errOnRestartAccount) ? "" : "<span class=\"text-accent\">{$errOnRestartAccount}</span>") ?>
    </div>
    <h3>■ アカウント情報入力</h3>
    <form action="" method="post">
        <table class="form-table">
         	
            <tr>
                <th><label for="account_id">account_id</label></th>
                <td><input name="account_id" type="text" id="account_id" class="regular-text" value="<?php print($_POST['account_id']); ?>" /></td>
            </tr>
            <tr>
                <th><label for="account_apiprekey">account_apiprekey</label></th>
                <td><input name="account_apiprekey" type="text" id="account_apiprekey" class="regular-text" value="<?php print($_POST['account_apiprekey']); ?>" /></td>
            </tr>
        </table>
        <input type="hidden" name="restart_account" value="1" />
        <p class="submit"><input type="submit" name="Submit" class="button-primary" value="アカウント情報を登録する" /></p> 
    </form>
</div>
<?php
        } else {

            // アカウント新規登録ページの場合 
?>
<div class="wrap welcome_area">
    <h2>利用開始準備</h2> 
    <div class="info">
    	<?php 
    	if(isset($_GET['error'])): 
    	echo "<font color='red'>エラーが発生しました為、サービスを開始出来ませんでした。暫く経ってから再試行してください</font><br>"; 
    	endif;
    	?> 
        WP-BemoovePluginは、<a target="blank" href="http://www.bemoove.jp/"> ビムーブ株式会社 </a>  が提供するWordPress上で動画を簡単に配信することができる<br /> 
        プラグインです。<br /> 
        このプラグインは無料でどなたでもご利用いただけます。<br /> 
        ご利用にあたっては以下の利用規約をご確認いただき、利用開始ボタンをクリックしてください。 
    </div>
    <div style="max-width: 750px;">
        <div id="privacy_area">
            <iframe width="750" height="200" src="https://www.bemoove.jp/privacy/service_behlsdev.html" seamless="seamless" class="inbox"></iframe>
        </div>
        <div class="form_area">
            <form action="" method="post">
                <input type="hidden" name="register_account" value="1" />
                <div>
                    <input type="submit" name="Submit" class="button-primary" value="WP-BemoovePluginの利用を開始する（無料）" />
                </div>
            </form>
        </div>
        <div class="restart_area">
            <a href="admin.php?page=BeMoOve_welcome&restart=1">以前作成したWP-BemoovePluginのアカウント情報を引き継ぐ方はこちら</a> 
        </div>
    </div>
</div>
<?php
        }
    } 

    /**
     * アカウント設定画面の表示を行う。 
     */
    function BeMoOve_Admin_Page() {

    	$bmClassf_ = new wp_cgmCLass(); $bm_dsubDvalue = $bmClassf_->get_wpbmSubDomain_set(); $bm_dsubDvalue_acct_type = $bmClassf_->get_wpbmSubDomain_set2();


        if ($_POST['remove_account'] == '1') {
            $apiClient = new BeHLSApiClient($this->getUserAccountInfo());
            $userAccountInfo = $apiClient->removeAcount();

            if ($userAccountInfo && $userAccountInfo[removeAccount][item][activate] == 'T') {
                $this->getUserAccountInfo()->remove();
                $this->getWPMovieMetaDataAdapter()->deleteAll();

                // 結果ページへリダイレクト
                $location = admin_url() . 'admin.php?page=BeMoOve_welcome&deleted=1';
                die("<script type=\"text/javascript\">(function() { location.href = \"{$location}\"; })();</script>");
            }
        }

        $isEdited = false; // アカウントの変更を行ったか否か 
        $isAccountActivate = false; // アクティブなアカウントか否か 
        $editErrMsg = '';
        $editInfoMsg = '';
        $accountdata = null;

        // アカウント設定保存ボタン押下時  
        if ($_POST['edit_account'] == '1') {
            check_admin_referer('edit_account');
            $opt = $_POST[UserAccountInfo::OPTION_KEY];
            $newAccountId = $opt[UserAccountInfo::ACCOUNT_ID_PARAM_KEY];
            $newAccountApiprekey = $opt[UserAccountInfo::ACCOUNT_APIPREKEY_PARAM_KEY];
            $bmSub_wpdomain = $_POST['bmsubdomain'];
            if (empty($newAccountId)) {
                $editErrMsg .= 'account_idは必須入力項目です。<br />'; 
            }
            if (empty($newAccountApiprekey)) {
                $editErrMsg .= 'account_apiprekeyは必須入力項目です。<br />'; 
            }

            if (empty($editErrMsg)) {
                $oldAccountId = $this->getUserAccountInfo()->getAccountId();
                $oldAccountApiprekey = $this->getUserAccountInfo()->getAccountApiprekey();
                if ($newAccountId == $oldAccountId && $newAccountApiprekey == $oldAccountApiprekey && $bmSub_wpdomain == $bm_dsubDvalue) {
                    $editErrMsg .= 'アカウント情報に変更がありません。<br />';  
                }

                if (empty($editErrMsg)) {
                    $userAccountInfo = UserAccountInfo::createInstance(
                        $newAccountId
                        , $newAccountApiprekey
                    );
                    $this->setUserAccountInfo($userAccountInfo);
                    $isEdited = true;
                }
                $apiClient = new BeHLSApiClient($this->getUserAccountInfo());
                $accountdata = $apiClient->getAccount($bmSub_wpdomain);   //<--------------- V 1.4用の更新プログラム
                $isAccountActivate = $accountdata && $accountdata[getAccount][item][activate] == 'T';
                if ($isEdited) {
                    if ($isAccountActivate) {
                        $userAccountInfo->save();
                        $this->syncAccountData();
                        $editInfoMsg .= 'アカウント情報を変更しました。<br />'; 
                        $editInfoMsg .= 'このアカウントの動画データが自動的に同期されました。<br />'; 
                    } else {
                        $editErrMsg .= '入力されたアカウント情報が正しくありません。<br />'; 
                    }
                }
            }
        } else {

            if ($_POST['sync_account'] == '1') {
                // アカウント同期ボタン押下時 
                $this->syncAccountData();
                $editInfoMsg .= 'このアカウントの動画データを同期しました。<br />';  
            }

            $apiClient = new BeHLSApiClient($this->getUserAccountInfo());
            $accountdata = $apiClient->getAccount($bmSub_wpdomain);   //<--------------- V 1.4用の更新プログラム
            $isAccountActivate = $accountdata && $accountdata[getAccount][item][activate] == 'T';
        }


        if ($isAccountActivate) {
            $max_strage_capacity = $accountdata[getAccount][item][quota] / 1024 / 1024;
            $max_strage_capacity = floor($max_strage_capacity * 10);
            $max_strage_capacity = $max_strage_capacity / 10;

            $dispstrage = $accountdata[getAccount][item][disk_used] / 1024 / 1024;
            $dispstrage = floor($dispstrage * 10);
            $dispstrage = $dispstrage / 10;

            $used_rate = (0 < $max_strage_capacity) ? ($dispstrage * 100 / $max_strage_capacity) : 0;
            $used_rate = floor($used_rate * 100);
            $used_rate = $used_rate / 100;
        }

?>

<div class="wrap account_setting_content">
    <h2>アカウント設定</h2> 
    <?php print(empty($editInfoMsg) ? "" : "<div class=\"fade_msg_box\" style=\"color:#fff!important;background-color:#000!important;\"><span>{$editInfoMsg}</span></div>") ?>
    <div class="account_setting_detail">
        <h3>■ アカウント情報</h3> 
        <?php print(empty($editErrMsg) ? "" : "<div><span class=\"text-accent\">{$editErrMsg}</span></div>") ?>
        <form action="" method="post">
            <?php wp_nonce_field('edit_account'); ?>
            <table class="form-table">
            	<!--<tr>
                	<th><label for="account_id">account_type</label></th>
                	<td><label style="font-weight:bold;"><?php //print($bm_dsubDvalue_acct_type); ?></label></td>
           		</tr>-->
                <tr>
                    <th><label for="inputtext3">account_id</label></th>
                    <td><input name="<?php print(UserAccountInfo::OPTION_KEY . '[' . UserAccountInfo::ACCOUNT_ID_PARAM_KEY . ']') ?>"
                               type="text" id="inputtext3" class="regular-text"
                               value="<?php print($this->getUserAccountInfo()->getAccountId()); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="inputtext4">account_apiprekey</label></th>
                    <td><input name="<?php print(UserAccountInfo::OPTION_KEY . '[' . UserAccountInfo::ACCOUNT_APIPREKEY_PARAM_KEY . ']') ?>"
                               type="text" id="inputtext4" class="regular-text"
                               value="<?php print($this->getUserAccountInfo()->getAccountApiprekey()); ?>" /></td>
                </tr>
                <!--<tr>
                	
                    <th><label for="inputtext4">subdomain_name</label></th>
                    <td><input name="bmsubdomain"
                               type="text" id="inputtext4" class="regular-text"
                               value="<?php //echo $bm_dsubDvalue; ?>" /></td>
                </tr>-->
<?php
            if ($isAccountActivate === true) {
                echo "<tr><th>ストレージ使用率</th><td>{$used_rate}%&nbsp;({$dispstrage}&nbsp;/&nbsp;{$max_strage_capacity}&nbsp;MB)</td></tr>"; 
            }
?>
            </table>
            <input type="hidden" name="edit_account" value="1" />
            <p class="submit">
                <input type="submit" name="Submit" class="button-primary" value="アカウント情報を変更する" /> 
            </p>
        </form>
        <div class="info">
            ※ 別途取得したWP-BemoovePluginのアカウントを利用したい場合は、そのアカウント情報を設定<br />
            することで、そのアカウント利用環境を復元できます。<br /> 
        </div>
    </div>
    <div class="account_setting_detail">
        <h3>■ アカウント同期</h3> 
        <form action="" method="post">
            <input type="hidden" name="sync_account" value="1" />
            <p class="submit">
                <input type="submit" name="Submit" class="button-primary" value="アカウント情報を同期する" /> 
            </p>
        </form>
        <div class="info">
            ※ アカウント情報を同期することで、すでに登録された動画情報と同期をとることができます。<br /> 
        </div>
    </div>
    <div class="account_setting_detail">
        <h3>■ アカウント削除</h3>
        <form action="" method="post">
            <?php wp_nonce_field('remove_account'); ?>
            <input type="hidden" name="remove_account" value="1" />
            <p class="submit">
                <input type="submit" name="Submit" class="button-primary" value="アカウント情報を削除する"  
                       onclick="return confirm('アカウントを削除します。よろしいですか？');" /> 
            </p>
        </form>
        <div class="info">
            ※ 一度削除したアカウント情報は復元できませんのでご注意ください。<br /> 
            ※ アカウントを削除すると登録した動画データも削除されます。<br /> 
        </div>
    </div>
</div>
<?php
    }  



    private function syncAccountData() {
        $apiClient = new BeHLSApiClient($this->getUserAccountInfo());
        $video_list_data = $apiClient->listVideo();

        // 動画が存在しない場合
        if ($video_list_data[message][code] == '102') {
            $this->getWPMovieMetaDataAdapter()->deleteAll();
            return 0;
        }

        $video_item_list = $video_list_data[listVideo];

        // APIで取得した動画一覧がWP側のDBにあれば更新、なければ追加を行う。 
        // APIで取得できなかった動画は削除する。   
        // 重複したタグ名がAPIから取得された場合は、先頭に取得してきたもを優先し、後続のものは無視する  
        $wp_movie_records = $this->getWPMovieMetaDataAdapter()->getAllData();

        // APIで取得できなかった動画をWPから削除 
        $wp_delete_target = array();
        foreach ($wp_movie_records as $record) {
            $is_delete_target = true;
            foreach ($video_item_list[item] as $vide_item) {
                if ($vide_item[video][tag] == $record->name) {
                    $is_delete_target = false;
                    break;
                }
            }

            if ($is_delete_target) {
                array_push($wp_delete_target, $record->name);
            }
        }
        if (0 < count($wp_delete_target)) {
            $this->getWPMovieMetaDataAdapter()->deleteByNames($wp_delete_target);
        }

        $dealed_movie_names = array();
        foreach ($video_item_list[item] as $vide_item) {

            $video_size = explode("x", $vide_item[video][s]);

            // 数値が取得できなかったら更新しない   
            // 新規登録直後には##video_s##という文字列で返却される場合がある    
            if (!is_numeric($video_size[0])) continue;

            $vide_name = $vide_item[video][tag];
            if (in_array($vide_name, $dealed_movie_names)) continue;
            array_push($dealed_movie_names, $vide_name);

            $set_arr = array(
                'name' => $vide_item[video][tag]
                , 'hash_name' => $vide_item[video][hash]
                , 'video_hash' => $vide_item[video][hash]
                , 'video_file' => $vide_item[video][file_hash]
                , 'video_width' => $video_size[0]
                , 'video_height' => $video_size[1]
                , 'video_time' => $vide_item[video][duration]
                , 'thumbnail_hash' => $vide_item[thumbnail][hash]
                , 'thumbnail_file' => $vide_item[thumbnail][file_hash]
                , 'callback_date' => date('Y-m-d H:i:s')
                , 'redirectSuccess_code' => 300
                , 'flag' => '0'
            );

            // すでにWP側のDBにあるかをチェック    
            $wp_has_target = false;
            foreach ($wp_movie_records as $record) {
                if ($record->name == $vide_name) {
                    $wp_has_target = true;
                    break;
                }
            }

            if ($wp_has_target) {
                // すでにWP側のDBにある場合    
                $this->getWPMovieMetaDataAdapter()->update($set_arr, array('name' => $vide_name));
            } else {
                // WP側のDBにない場合   
                $this->getWPMovieMetaDataAdapter()->insert($set_arr);
            }
        }

        return count($dealed_movie_names);
    } 

    /**
     * 新規追加メニューの画面表示を行う。   
     */
    function BeMoOve_Input_Page() {
        // ファイルアップロード画面の場合   
        if ($_GET['m'] == 'file_upload') {
?>
            <div class="wrap">
            <h2>動画アップロード</h2> 

<?php
            $opt = get_option('BeMoOve_admin_datas');
            $account_id = isset($opt['account_id']) ? $opt['account_id'] : null;
            $account_apiprekey = isset($opt['account_apiprekey']) ? $opt['account_apiprekey'] : null;
            $dt = date("YmdHis");
            $account_apikey = md5($account_apiprekey . $dt);
            $hash = md5($dt.rand());
            $video_tag = $_POST['movie_name'];
            if (!$video_tag) {
                $video_tag = $_GET['movie_name'];
            }
            $is_detail = $_GET['fuo'] == 'detail';

            if ($video_tag) {
?>
                <form action="https://<?php print($this->getUserAccountInfo()->getBehlsHost()); ?>/video/upload" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php print($account_id); ?>" />
                    <input type="hidden" name="apikey" value="<?php print($account_apikey); ?>" />
                    <input type="hidden" name="dt" value="<?php print($dt); ?>" />
                    <input type="hidden" name="tag" value="<?php print($video_tag); ?>" />
                    <input type="hidden" name="removeOrigin" value="T" />
                    <input type="hidden" name="redirectSuccess" value="<?php print(site_url()); ?>/wp-admin/admin.php?page=BeMoOve_new&m=success&n=<?php print(urlencode($video_tag));?>&t=<?php print($hash); ?>" />
                    <input type="hidden" name="redirectFailure" value="<?php print(site_url()); ?>/wp-admin/admin.php?page=BeMoOve_new&m=failure" />
                    <input type="hidden" name="preset" value="veryfast" />

                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row"><label for="inputtext">アップロード動画名</label></th> 
                            <td><?php print($video_tag); ?></td>
                        </tr>
                    </table>
                    <table class="form-table">
                        <tr>
                            <td><h2 class="bemoove_title">アップロードファイル</h2></td> 
                        </tr>
                        <tr>
                            <td><input type="file" name="myVideo" /></td>
                        </tr>
                        <tr>
                            <td><p class="bemoove_description">※ アップできる容量は1GBまで / WMV/AVI/MPG/MPEG/MOV/M4V/3GP/3G2/FLV/MP4/TS/OGG/WEBM/MTS形式でアップ可能</p></td> <!-- Capacity that can be up ※ The 1GB up / WMV / AVI / MPG / MPEG / MOV / M4V / 3GP / 3G2 / FLV / MP4 / TS / OGG / WEBM / possible up in MTS format -->
                        </tr>
                        <tr>
                            <td><h2 class="bemoove_title">画面サイズ</h2></td> 
                        </tr>
                        <tr>
                            <td><select name="s">
 <?php
                             // 画面サイズのセレクトボックスのオプション出力 
                             $rects = array(
                                 new Rect(128, 96)
                                 , new Rect(160, 120)
                                 , new Rect(320, 240)
                                 , new Rect(480, 360)
                                 , new Rect(640, 480)
                                 , new Rect(768, 576)
                                 , new Rect(800, 600)
                                 , new Rect(1024, 768)
                                 , new Rect(1280, 960)
                                 , new Rect(1600, 1200)
                                 , new Rect(1920, 1440)
                                 , new Rect(128, 72)
                                 , new Rect(160, 90)
                                 , new Rect(320, 180)
                                 , new Rect(480, 270)
                                 , new Rect(640, 360)
                                 , new Rect(768, 432)
                                 , new Rect(800, 450)
                                 , new Rect(1024, 576)
                                 , new Rect(1280, 720)
                                 , new Rect(1600, 900)
                                 , new Rect(1920, 1080)
                             );
                             $defaultWidth = 320;
                             $defaultHeight = 180;

                             foreach ($rects as $rect) {

                                 print("<option value=\"{$rect->getWidth()}x{$rect->getHeight()}\""
                                       . (($rect->getWidth() === $defaultWidth && $rect->getHeight() === $defaultHeight) ? " selected" : "")
                                       . ">"
                                       . "{$rect->getWidth()}&#215;{$rect->getHeight()}"
                                       . "&nbsp;({$rect->getRatioWidth()}&nbsp;:&nbsp;{$rect->getRatioHeight()})</option>");
                             }
 ?>
                            </select></td>
                        </tr>
                        <tr>
                            <td><p class="bemoove_description">※ 変換後の画面サイズを指定します。縦横の比率は元の動画と合わせてください。</p></td> 
                        </tr>

<?php
                // ファイルアップロードの詳細オプションバージョン画面の場合    
                if ($is_detail) {
?>
                        <tr>
                            <td><h2 class="bemoove_title">フレームレート</h2></td>
                        </tr>
                        <tr>
                            <td><select name="r">
                                <option value="15">15</option>
                                <option value="24">24</option>
                                <option value="25">25</option>
                                <option value="29.97" selected>29.97</option>
                                <option value="30">30</option>
                                <option value="60">60</option>
                            </select></td>
                        </tr>
                        <tr>
                            <td><p class="bemoove_description">※ 変換後の動画フレームレートを指定する。 / デフォルト値（29.97） </p></td> 
                        </tr>

                        <tr>
                            <td><h2 class="bemoove_title">動画ビットレート</h2></td>
                        </tr>
                        <tr>
                            <td><select name="b">
                            <option value="32">32</option>
                            <option value="64">64</option>
                            <option value="128">128</option>
                            <option value="256">256</option>
                            <option value="384">384</option>
                            <option value="512">512</option>
                            <option value="768">768</option>
                            <option value="1024" selected>1024</option>
                            <option value="1280">1280</option>
                            <option value="1536">1536</option>
                            <option value="1792">1792</option>
                            <option value="2048">2048</option>
                            <option value="2304">2304</option>
                            <option value="2560">2560</option>
                            <option value="2816">2816</option>
                            <option value="3072">3072</option>
                            <option value="3328">3328</option>
                            <option value="3584">3584</option>
                            <option value="3840">3840</option>
                            <option value="4096">4096</option>
                            </select></td>
                        </tr>
                        <tr>
                            <td><p class="bemoove_description">※ 変換後の動画ビットレートを指定する。 /デフォルト値（1024）</p></td> 
                        </tr>
                        <tr>
                            <td><h2 class="bemoove_title">音声サンプリングレート</h2></td> 
                        </tr>
                        <tr>
                            <td><select name="ar">
                                <option value="8000">8000</option>
                                <option value="11025">11025</option>
                                <option value="16000">16000</option>
                                <option value="22050">22050</option>
                                <option value="24000">24000</option>
                                <option value="32000">32000</option>
                                <option value="44100" selected>44100</option>
                                <option value="48000">48000</option>
                                <option value="96000">96000</option>
                            </select></td>
                        </tr>
                        <tr>
                            <td><p class="bemoove_description">※ 変換後の音声サンプリングレートを指定する。 / デフォルト値（44100）</p></td> 
                        </tr>
                        <tr>
                            <td><h2 class="bemoove_title">音声ビットレート</h2></td> 
                        </tr>
                        <tr>
                            <td><select name="ab">
                                <option value="16">16</option>
                                <option value="32">32</option>
                                <option value="48">48</option>
                                <option value="64">64</option>
                                <option value="96">96</option>
                                <option value="128" selected>128</option>
                                <option value="160">160</option>
                                <option value="192">192</option>
                                <option value="256">256</option>
                                <option value="320">320</option>
                            </select></td>
                        </tr>
                        <tr>
                            <td><p class="bemoove_description">※ 変換後の音声ビットレートを指定する。 / デフォルト値（128）</p></td> 
                        </tr>
                        <tr>
                            <td><h2 class="bemoove_title">音声チャンネル</h2></td> 
                        </tr>
                        <tr>
                            <td><select name="ac">
                                <option value="2" selected>2</option>
                                <option value="6">6</option>
                            </select></td>
                        </tr>
                        <tr>
                            <td><p class="bemoove_description">※ 変換後の音声チャンネルを指定する。 / デフォルト値（2） </p></td> 
                        </tr>
                        <tr>
                            <td><h2 class="bemoove_title">プロファイル</h2></td> 
                        </tr>
                        <tr>
                            <td><select name="profile">
                                <option value="baseline" selected>baseline</option>
                                <option value="main">main</option>
                                <option value="high">high</option>
                            </select></td>
                        </tr>
                        <tr>
                            <td><p class="bemoove_description">※ 変換後のプロファイルを指定する。 / デフォルト値（baseline） </p></td> 
                        </tr>
                        <tr>
                            <td><h2 class="bemoove_title">レベル</h2></td> 
                        </tr>
                        <tr>
                            <td><select name="level">
                                <option value="10">10</option>
                                <option value="11">11</option>
                                <option value="12">12</option>
                                <option value="13">13</option>
                                <option value="20">20</option>
                                <option value="21">21</option>
                                <option value="22">22</option>
                                <option value="30" selected>30</option>
                                <option value="31">31</option>
                                <option value="32">32</option>
                                <option value="40">40</option>
                                <option value="41">41</option>
                                <option value="42">42</option>
                                <option value="50">50</option>
                                <option value="51">51</option>
                            </select></td>
                        </tr>
                        <tr>
                            <td><p class="bemoove_description">※  変換後のレベルを指定する。 / デフォルト値（30）</p></td> 
                        </tr>
<?php
                // ファイルアップロードの通常オプションバージョン画面の場合  
                } else {
?>
                        <tr>
                            <td><h2 class="bemoove_title">動画の滑らかさ</h2></td> 
                        </tr>
                        <tr>
                            <td>
                            <select id="file_upload_spec_list">
                                <option value="l">低</option>  
                                <option value="m" selected>中</option> 
                                <option value="h">高</option> 
                            </select>
                            <input type="hidden" name="r" value="24" />
                            <input type="hidden" name="b" value="512" />
                            <input type="hidden" name="ar" value="32000" />
                            <input type="hidden" name="ab" value="64" />
                            </td>
                        </tr>
                        <tr>
                            <td><p class="bemoove_description">※ 滑らかに動画を再生したい方は「高」をお選びください。</p></td> 
                        </tr>
<?php
                }
?>
                        <tr>
                            <td><h2 class="bemoove_title">サムネイル</h2></td> 
                        </tr>
                        <tr>
                            <td>
                                <input type="text" name="thumbnailTime" size="30" value="00:00:05.0" />
                            </td>
                        </tr>
                        <tr>
                            <td><p class="bemoove_description">※ サムネイルを作成する時間を指定する。 / デフォルト値（5秒）</p></td> 
                        </tr>
                    </table>
                    <p class="submit"><input type="submit" name="Submit" class="button-primary" value="アップロード" /></p>
                </form>

                <div>
<?php
                // ファイルアップロードの詳細オプションバージョン画面の場合 
                if ($is_detail) {
?>
                    <h2><a href="admin.php?page=BeMoOve_new&m=file_upload&movie_name=<?php print($video_tag) ?>" class="add-new-h2">戻る</a></h2> 

<?php
                } else {
?>
                    <h2><a href="admin.php?page=BeMoOve_new&m=file_upload&movie_name=<?php print($video_tag) ?>&fuo=detail" class="add-new-h2">詳細情報を手動で設定してアップロード</a></h2> 
<?php
                }
?>
                </div>
<?php
            } else {
?>
アップロード動画名が空白です。
<?php
            }
?>
            </div>
<?php
        // ファイルアップロード成功画面の場合   
        } elseif ($_GET['m'] == 'success') {

            $name = isset($_GET['n']) ? $_GET['n'] : null;
            $hash_name = isset($_GET['t']) ? $_GET['t'] : null;
            $code = isset($_GET['code']) ? $_GET['code'] : null;

            $set_arr = array(
                'name' => $name
                , 'hash_name' => $hash_name
                , 'redirectSuccess_code' => 300
                , 'social_share_flag' => 1
                , 'flag' => 1
            );

            // 同名のタグがすでにあれば削除した後に追加    
            $same_name_records = $this->getWPMovieMetaDataAdapter()->getDataByName($name);

            if ($same_name_records && 0 < count($same_name_records)) {
                $apiClient = new BeHLSApiClient($this->getUserAccountInfo());
                foreach ($same_name_records as $snr) {
                    // BeHLs側の古いファイルを削除
                    $apiClient->removeVideo($snr->video_hash);
                }
                $this->getWPMovieMetaDataAdapter()->deleteByName($name);
            }

            $this->getWPMovieMetaDataAdapter()->insert($set_arr);

?>
<div class="wrap">
    <h2>動画のアップロード</h2>  <!-- Upload videos -->
    動画のアップロードを完了しました。現在、動画変換処理中となりますので、しばらくお待ちください。<br /> 
    <a href="admin.php?page=BeMoOve_movies_list">動画一覧へ</a> 
</div>
<?php
        // ファイルアップロード失敗画面の場合    
        } elseif ($_GET['m'] == 'failure') {
?>
<div class="wrap">
<h2>動画のアップロード</h2> 
動画投稿に失敗しました。<br /> 
<a href="admin.php?page=BeMoOve_movies_list">動画一覧へ</a> 
</div>
<?php
        // 初期画面の場合（新規追加） 
        } else {
?>
<div class="wrap">
    <h2>動画のアップロード</h2>
    <form action="admin.php?page=BeMoOve_new&m=file_upload" method="post">
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="inputtext">アップロード動画名</label></th> 
                <td><input name="movie_name" type="text" id="inputtext" pattern="^[0-9A-Za-z]+$" value="" placeholder="半角英数で入力してください(80文字まで) " class="regular-text" maxlength="80"/></td> 
            </tr>
        </table>
        <p class="submit"><input type="submit" name="Submit" class="button-primary" value="次へ" /></p> 
    </form>
</div>
<?php
        }
    } 


    /**
     * BeMoOveメニュー画面の表示を行う。   
     * 動画一覧および動画詳細ページの表示を行う。   
     */
    function BeMoOve_Movies_List_Page() {
        // 詳細画面の場合
        $isEdit = $_GET['m'] == 'edit' || $_POST['edit'] == '1';
        if ($isEdit) {
            $video_hash = $_GET['hash'];
            if (empty($video_hash)) $video_hash = $_POST['hash'];
            $editMsg = '';

            if ($_POST['edit'] == '1') {
                // 保存ボタン押下時 
                // サムネ画像の編集 
                $thumbnail_file_path = $_POST['thumbnail_file_path'];
                if (empty($thumbnail_file_path) || $thumbnail_file_path == 'default') {
                    $set_arr = array('override_thumbnail_file' => null);
                } else {
                    $set_arr = array('override_thumbnail_file' => $thumbnail_file_path);
                }

                // ソーシャル連携の編集  
                $social = $_POST['social'] == '1';
                $set_arr += array('social_share_flag' => $social ? 1 : 0);

                // ロゴの部分   
                $logoFile = $_POST['logo_file'];
                $set_arr += array('logo_file' => $logoFile);
                $logoLink = $_POST['logo_link'];
                $set_arr += array('logo_link' => $logoLink);

                $this->getWPMovieMetaDataAdapter()->update($set_arr, array('video_hash' => $video_hash));
                $editMsg .= '設定を保存しました。'; 
            }
?>
    <div class="wrap">
        <h2>Video editing <!--動画編集-->&nbsp;
            <a href="admin.php?page=BeMoOve_movies_list&m=details&hash=<?php print($video_hash) ?>" class="add-new-h2">戻る</a>&nbsp; 
            <a href="admin.php?page=BeMoOve_movies_list" class="add-new-h2">動画一覧へ</a> 
        </h2>

<?php
            print(empty($editMsg) ? "" : "<div class='fade_msg_box'>$editMsg</div>");
            $targetVideoHashRecords = $this->getWPMovieMetaDataAdapter()->getDataByVideoHash($video_hash);
            $beMoOveTag = new BeMoOveTag($targetVideoHashRecords[0]);
            print($beMoOveTag->getEmbedSrc($this->getUserAccountInfo(), true));
?>
        <br />
        <form action="" method="post">
            <table class="edit">
                <tr>
                    <th class="short" colspan="2"><label for="thumbnail_file_path">サムネイルファイル</label></th>
                    <td class="short"><input type="text" id="thumbnail_file_path" name="thumbnail_file_path" style="width: 100%;"
                        value="<?php print($beMoOveTag->getDispThumbnailFile($this->getUserAccountInfo())) ?>" /></td>
                </tr>
                <tr>
                    <th class="short" colspan="2">ソーシャル連携</th> 
                    <td class="short" class="short">
                        <input id="social_on" type="radio" name="social" value="1" <?php print($beMoOveTag->isSocialShare() ? 'checked="checked"' : '') ?> />
                        <label for="social_on">ON</label>
                        <input id="social_off" type="radio" name="social" value="0" <?php print($beMoOveTag->isSocialShare() ? '' : 'checked="checked"') ?> />
                        <label for="social_off">OFF</label>
                    </td>
                </tr>
                <tr>
                    <th rowspan="2">ロゴ</th> 
                    <th><label for="logo_file">ファイル</label></th> 
                    <td class="short"><input type="text" id="logo_file" name="logo_file" style="width: 100%;"
                        value="<?php print($beMoOveTag->getLogoFile()) ?>" placeholder="http://www.bemoove.jp/images/header_logo.gif" /></td>
                </tr>
                <tr>
                    <th><label for="logo_link">リンク</label></th> 
                    <td class="short"><input type="text" id="logo_link" name="logo_link" style="width: 100%;"
                        value="<?php print($beMoOveTag->getLogoLink()) ?>" placeholder="http://www.bemoove.jp/" /></td>
                </tr>
            </table>
            <input type="hidden" name="edit" value="1" />
            <input type="hidden" name="hash" value="<?php print($video_hash) ?>" />
            <p class="submit">
                <input type="submit" name="Submit" class="button-primary" value="設定を保存する" /> 
            </p>
        </form>
        <div class="info">
            ※ サムネイルファイルは、ファイルのURLを指定してください。 <br /> 
            ※ サムネイルファイルは、入力を空にして保存することで、アップロード時のものに戻すことができます。<br />
            ※ ソーシャル連携をONにすることで、閲覧ページにて動画にソーシャル連携用のオーバーレイが表示されます。 <br />
            ※ ロゴファイルとロゴリンクを設定することで、動画右上隅にロゴを表示することができます。ロゴをクリックすると、設定したロゴリンクに遷移させることができます<br />
            ※ ロゴファイルはファイルのURLを、ロゴリンクは遷移させたいページのURLをそれぞれ指定してください。<br /> 
        </div>
    </div>
<?php
        } elseif ($_GET['m'] == 'details') {
            $apiClient = new BeHLSApiClient($this->getUserAccountInfo());
            $video_hash = $_GET['hash'];
            $override_thumbnail_file = $_GET['otf'];
?>
    <div class="wrap">
        <h2>動画詳細,  戻る <a href="admin.php?page=BeMoOve_movies_list" class="add-new-h2">Go back</a></h2> 
<?php
            $targetVideoHashRecords = $this->getWPMovieMetaDataAdapter()->getDataByVideoHash($video_hash);
            $beMoOveTag = new BeMoOveTag($targetVideoHashRecords[0]);
            print($beMoOveTag->getEmbedSrc($this->getUserAccountInfo(), true));
            print("<br />");

            $data = $apiClient->getVideo($video_hash);
?>
            <table class="detail">
                <tr><th colspan="2">動画 </th></tr> 
                <tr><th class="short">tag</th><td class="short"><?php echo $data[getVideo][item][video][tag] ?></td></tr>
                <tr><th class="short">hash</th><td class="short"><?php echo $data[getVideo][item][video][hash] ?></td></tr>
                <tr><th class="short">file_tag</th><td class="short"><?php echo $data[getVideo][item][video][file_tag] ?></td></tr>
                <tr><th class="short">file_hash</th><td class="short"><?php echo $data[getVideo][item][video][file_hash] ?></td></tr>
                <tr><th class="short">file_path</th><td class="short"><?php echo $data[getVideo][item][video][file_path] ?></td></tr>
                <tr><th class="short">size</th><td class="short"><?php echo $data[getVideo][item][video][size] ?></td></tr>
                <tr><th class="short">duration</th><td class="short"><?php echo $data[getVideo][item][video][duration] ?></td></tr>
                <tr><th class="short">convert_time</th><td class="short"><?php echo $data[getVideo][item][video][convert_time] ?></td></tr>
                <tr><th class="short">s</th><td class="short"><?php echo $data[getVideo][item][video][s] ?></td></tr>
                <tr><th class="short">aspect</th><td class="short"><?php echo $data[getVideo][item][video][aspect] ?></td></tr>
                <tr><th class="short">r</th><td class="short"><?php echo $data[getVideo][item][video][r] ?></td></tr>
                <tr><th class="short">b</th><td class="short"><?php echo $data[getVideo][item][video][b] ?></td></tr>
                <tr><th class="short">ar</th><td class="short"><?php echo $data[getVideo][item][video][ar] ?></td></tr>
                <tr><th class="short">ab</th><td class="short"><?php echo $data[getVideo][item][video][ab] ?></td></tr>
                <tr><th class="short">ac</th><td class="short"><?php echo $data[getVideo][item][video][ac] ?></td></tr>
                <tr><th class="short">profile</th><td class="short"><?php echo $data[getVideo][item][video][profile] ?></td></tr>
                <tr><th class="short">level</th><td class="short"><?php echo $data[getVideo][item][video][level] ?></td></tr>
                <tr><th class="short">created_at</th><td class="short"><?php echo $data[getVideo][item][video][created_at] ?></td></tr>
                <tr><th colspan="2">サムネイル</th></tr> 
                <tr><th class="short">ファイルURL</th><td class="short"><?php echo $beMoOveTag->getDispThumbnailFile($this->getUserAccountInfo()) ?></td></tr> 
                <tr><th colspan="2">ソーシャル連携</th></tr> 
                <tr><th class="short">ON&nbsp;/&nbsp;OFF</th><td class="short"><?php echo $beMoOveTag->isSocialShare() ? "ON" : "OFF" ?></td></tr>
                <tr><th colspan="2">ロゴ</th></tr> 
                <tr><th class="short">ファイルURL</th><td class="short"><?php echo $beMoOveTag->getLogoFile() ?></td></tr> 
                <tr><th class="short">リンクURL</th><td class="short"><?php echo $beMoOveTag->getLogoLink() ?></td></tr> 
            </table>
        <div style="margin-top: 20px;"><a href="admin.php?page=BeMoOve_movies_list&m=edit&hash=<?php print($video_hash) ?>" class="link_btn"> 設定を変更する</a></div> 
    </div>
<?php
        // 削除画面の場合
        } elseif ($_GET['m'] == 'delete') {

            $apiClient = new BeHLSApiClient($this->getUserAccountInfo());

            $videoHash = $_GET['hash'];
            $apiClient->removeVideo($videoHash);
            $this->getWPMovieMetaDataAdapter()->deleteByVideoHash($videoHash);
?>
            <div class="wrap">
            <h2>動画削除</h2> 

            削除しました。<br />
            <a href="admin.php?page=BeMoOve_movies_list">動画一覧へ</a> 
            </div>
<?php
        // 動画一覧画面の場合   
        } else {
?>
            <div class="wrap">
            <h2>動画一覧 <a href="admin.php?page=BeMoOve_new" class="add-new-h2">新規追加</a></h2> 
<?php
            if ($_GET['restart_account'] == '1') {
                print('<div class="fade_msg_box">アカウント設定を行いました。<br />アカウントの同期が完了しました。</div>'); 
            }

            $offset = 0;
            if (is_numeric($_GET["s"])) {
                $offset = $_GET["s"];
            }
            $get_list = $this->getWPMovieMetaDataAdapter()->getTopDataFromOffset($offset, WP_BeMoOve_ITEMS_LIMIT);
            $page_area = $this->get_pagination();
            if ($page_area) print("<table><tr><td>{$page_area}</td></tr></table>");

            // 動画情報登録処理を行い、その後表示処理を行う。
            $apiClient = new BeHLSApiClient($this->getUserAccountInfo());

            foreach ($get_list as $key => $val) {

                if ($val) {
                    // 既にデータがあったら更新しない  
                    if ($val->flag == '0') continue;

                    $data = $apiClient->getVideo($val->name);

                    // getできなかったら更新しない   
                    if ($data[message] && $data[message][code] == '102') continue;

                    $size = explode("x", $data[getVideo][item][video][s]);

                    // 数値が取得できなかったら更新しない     
                    // 新規登録直後には##video_s##という文字列で返却される場合がある   
                    if (!is_numeric($size[0])) continue;

                    $set_arr = array(
                        'video_hash' => $data[getVideo][item][video][hash]
                        , 'video_file' => $data[getVideo][item][video][file_hash]
                        , 'video_width' => $size[0]
                        , 'video_height' => $size[1]
                        , 'video_time' => $data[getVideo][item][video][duration]
                        , 'thumbnail_hash' => $data[getVideo][item][thumbnail][hash]
                        , 'thumbnail_file' => $data[getVideo][item][thumbnail][file_hash]
                        , 'callback_date' => date('Y-m-d H:i:s')
                        , 'flag' => 0
                    );

                    $this->getWPMovieMetaDataAdapter()->update($set_arr, array('name' => $val->name));
                }
            }

            // 動画一覧表示処理
            foreach ($get_list as $key => $val) {
                $beMoOveTag = new BeMoOveTag($val);
                print($beMoOveTag->createListItemInfo($this->getUserAccountInfo()));
            }

            if ($page_area) print("<table><tr><td>{$page_area}</td></tr></table>");
?>
            </div>
<?php
        }
    } 

    /**
     * Ajax通信で呼び出される。  
     * 動画一覧画面にて変換処理がまだ行われていない項目に対して、情報を取得し返却する。  
     * この中でも取得できない場合は、その旨を通知する。 
     */
    function get_bemoove_movie_listitem_Info() {

        $apiClient = new BeHLSApiClient($this->getUserAccountInfo());
        $tagName = $_POST["tag_name"];

        $same_name_records = $this->getWPMovieMetaDataAdapter()->getDataByName($tagName);
        if (!$same_name_records && count($same_name_records) < 1) die('error');

        $same_name_record = $same_name_records[0];

        // 既にデータがあったら更新せずにhtmlを作成して返却  
        if ($same_name_record->flag == '0') {
            $bemooveTag = new BeMoOveTag($same_name_record);
            die($bemooveTag->createListItemInfo($this->getUserAccountInfo()));
        }

        $data = $apiClient->getVideo($tagName);

        if ($data[message] && $data[message][code] == '102') die('error');

        $size = explode("x", $data[getVideo][item][video][s]);

        // 数値が取得できなかったら更新しない  
        // 新規登録直後には##video_s##という文字列で返却される場合がある 
        if (!is_numeric($size[0])) die('error');

        $set_arr = array(
            'video_hash' => $data[getVideo][item][video][hash]
            , 'video_file' => $data[getVideo][item][video][file_hash]
            , 'video_width' => $size[0]
            , 'video_height' => $size[1]
            , 'video_time' => $data[getVideo][item][video][duration]
            , 'thumbnail_hash' => $data[getVideo][item][thumbnail][hash]
            , 'thumbnail_file' => $data[getVideo][item][thumbnail][file_hash]
            , 'callback_date' => date('Y-m-d H:i:s')
            , 'flag' => '0'
        );
        $this->getWPMovieMetaDataAdapter()->update($set_arr, array('name' => $tagName));

        // メモリーデータ更新
        $same_name_record->video_hash = $set_arr['video_hash'];
        $same_name_record->video_file = $set_arr['video_file'];
        $same_name_record->video_width = $set_arr['video_width'];
        $same_name_record->video_height = $set_arr['video_height'];
        $same_name_record->video_time = $set_arr['video_time'];
        $same_name_record->thumbnail_hash = $set_arr['thumbnail_hash'];
        $same_name_record->thumbnail_file = $set_arr['thumbnail_file'];
        $same_name_record->callback_date = $set_arr['callback_date'];
        $same_name_record->flag = $set_arr['flag'];

        $bemooveTag = new BeMoOveTag($same_name_record);
        die($bemooveTag->createListItemInfo($this->getUserAccountInfo()));
    } 

    function cmt_activate(WPMovieMetaDataAdapter $wPMovieMetaDataAdapter) {
        $cmt_db_version = '1.2.0';
        $installed_ver = get_option('cmt_meta_version');
        // テーブルのバージョンが違ったら作成
        if ($installed_ver != $cmt_db_version) {
            $wPMovieMetaDataAdapter->createTable();
            update_option('cmt_meta_version', $cmt_db_version);
        }
    } 
    function get_pagination() {
        $m_hr = WP_BeMoOve_ITEMS_LIMIT; //表示最大数   

        if ($_GET["s"] == "") $_GET["s"] = 0;

        $max = $this->getWPMovieMetaDataAdapter()->getCount();
        $start = $_GET['s'];
        $page = $max / $m_hr;

        if ($page <= 1) return "";

        $end = $start + $m_hr;

        for($m = 0; $m < $page; $m++){
            $p = $m + 1;
            $s = $m * $m_hr;
            if ($s == $_GET[s]) {
                $page_h .= "
                <div class=page_on>".$p."</div>
                ";
            } else {
                $page_h .= "
                <div class=page><a href=\"admin.php?page=BeMoOve_movies_list&s=".$s."\">".$p."</a></div>
                ";
            }
        }

        return $page_h;
    } 

    /**
     * 使い方ページの表示を行う。
     */
    function BeMoOve_Help_Page() {
?>
        <div class="wrap">
            <h2>使い方</h2>
            <div class="help_content">
                <div class="help_detail">
                    <h3>■ 動画のアップロード </h3> 
                    <ul>
                        <li>(1)&nbsp;WordPress管理画面左メニューの「新規追加」項目をクリック</li> 
                        <li>(2)&nbsp;動画名を入力（半角英数80文字まで）して「次へ」をクリック</li> 
                        <li>(3)&nbsp;アップロードファイルを「参照」し、各種項目を設定し「アップロード」ボタンをクリック</li> 
                        <li>(4)&nbsp;WordPress管理画面左メニューの「動画一覧」項目にて動画がアップロードされていることを確認 </li> 
                    </ul>
                </div>
                <div class="help_detail">
                    <h3>■ 動画の公開（WordPress投稿内の利用）</h3> 
                    <ul>
                        <li>(1)&nbsp;WordPress管理画面左メニューの「動画一覧」項目をクリック</li> 
                        <li>(2)&nbsp;公開したい動画の「貼り付け用タグ」項目にあるタグをコピー（例：[<?php print(BeMoOveTag::WP_BeMoOve_TAG_ATTR_NAME) ?>="Test"]）</li> 
                        <li>(3)&nbsp;記事投稿時の入力フォーム内の任意の位置に(2)のコードをペースト</li>
                        <li>(4)&nbsp;記事公開画面にて動画が表示されていることを確認</li>
                        <li>※ 貼り付け用タグを幅と高さを指定した記述にすることで、動画の表示サイズを変更できます。<br /> 
                        （例：[<?php print(BeMoOveTag::WP_BeMoOve_TAG_ATTR_NAME) ?>="Test(400, 300)"]と記述することで、動画サイズを&nbsp;400px&nbsp;×&nbsp;300px&nbsp;で表示できます。）</li> 
                    </ul>
                </div>
                <div class="help_detail">
                    <h3>■ 動画の公開（WordPress投稿外の利用）</h3>
                    <ul>
                        <li>(1)&nbsp;WordPress管理画面左メニューの「動画一覧」項目をクリック</li>
                        <li>(2)&nbsp;公開したい動画の「ソース」項目にあるタグをコピー（&lt;scriptで始まっている文字列）</li> 
                        <li>(3)&nbsp;公開したいHTMLファイルの任意の位置に(2)のコードをペースト</li> 
                        <li>(4)&nbsp;公開したいWebページにて動画が表示されていることを確認</li> 
                    </ul>
                </div>
                <div class="help_detail">
                    <h3>■ 動画の編集</h3>
                    <ul>
                        <li>(1)&nbsp;WordPress管理画面左メニューの「動画一覧」項目をクリック</li>
                        <li>(2)&nbsp;公開したい動画の画像をクリック </li>
                        <li>(3)&nbsp;動画詳細画面下部の「設定を変更する」ボタンをクリック</li> 
                        <li>(4)&nbsp;動画編集画面より、サムネイル画像等の編集が可能</li> 
                    </ul>
                </div>
                <div class="help_detail">
                    <h3>■ アカウントの移設</h3> 
                    <p>別ドメインのWordPressに現在のアカウントでアップロードした動画などを移設することができます。</p> 
                    <ul>
                        <li>(1)&nbsp;WordPress管理画面左メニューの「アカウント設定」項目をクリック</li> 
                        <li>(2)&nbsp;「account_id」と「account_apiprekey」の文字列を別途保存しておく" </li> 
                        <li>(3)&nbsp;移設先のWordPressに「WP-BemoovePlugin」をインストールし有効化する</li> 
                        <li>(4)&nbsp;利用開始準備画面にて「アカウントを持っている利用者」向けのリンクをクリック</li> 
                        <li>(5)&nbsp;別途保存していた「account_id」と「account_apiprekey」を登録 "</li> 
                    </ul>
                </div>
                <div class="help_detail">
                    <h3>■ ストレージ容量の追加 </h3> 
                    <p>ストレージ1GB制限を10GBにアップグレードすることが可能です。<br />詳しくは公式サイトをご覧いただくか、ビムーブ株式会社までお問い合わせください。</p> 
                    <ul>
                        <li><a target="_blank" href="http://www.bemoove.jp/lp/wpplugin/">ビムーブ公式サイト「WP-BemoovePlugin」紹介ページはこちら</a></li> 
                        <li><a target="_blank" href="https://www.bemoove.jp/contact/">ビムーブへのお問い合わせはこちら</a></li> 
                    </ul>
                </div>
            </div>
        </div>
<?php
    } 

}
