<?php
require_once(WP_BeMoOve__PLUGIN_DIR . 'util/Rect.php');

date_default_timezone_set('Asia/Tokyo');

class BeMoOve_Admin_Class {
    var $table_name;

    function __construct() {
        global $wpdb;
        // 接頭辞（wp_）を付けてテーブル名を設定
        $this->table_name = $wpdb->prefix . 'movie_meta';
        register_activation_hook (__FILE__, self::cmt_activate($this->table_name));

        // カスタムフィールドの作成
        add_action('admin_menu', array($this, 'add_pages'));
        add_action('admin_head', array($this, 'wp_custom_admin_css'), 100);
        add_action('admin_head', array($this, 'includeAdminJs'), 100);
        add_action('wp_ajax_get_bemoove_movie_listitem_Info', array($this, 'get_bemoove_movie_listitem_Info'));
    }

    function add_pages() {
        add_menu_page('BeMoOve','BeMoOve', 'level_8', 'BeMoOve_movies_list', array($this, 'BeMoOve_Movies_List_Page'), '', NULL);
        add_submenu_page('BeMoOve_movies_list', '新規追加', '新規追加', 'level_8', 'BeMoOve_new', array($this, 'BeMoOve_Input_Page'));
        add_submenu_page('BeMoOve_movies_list', 'アカウント設定', 'アカウント設定', 'level_8', 'BeMoOve_setting', array($this, 'BeMoOve_Admin_Page'));
        add_submenu_page('BeMoOve_movies_list', '使い方', '使い方', 'level_8', 'BeMoOve_help', array($this, 'BeMoOve_Help_Page'));
    }

    function wp_custom_admin_css() {
    	$url = WP_BeMoOve__PLUGIN_URL . 'css/style.css';
    	echo '<!-- custom admin css -->
        <link rel = "stylesheet" href = "'.$url.'" type="text/css" media="all" />
        <!-- /end custom adming css -->';
    }

    function includeAdminJs() {
    	$jsRoot = WP_BeMoOve__PLUGIN_URL . 'js';
    	print('<script src="' . $jsRoot . '/jquery-1.11.1.min.js" type="text/javascript"></script>'
        	. '<script src="' . $jsRoot . '/admin.js" type="text/javascript"></script>');
    }

    /**
     * アカウント設定画面の表示を行う。
     */
    function BeMoOve_Admin_Page() {

        $isAccountActivate = false; // アクティブなアカウントか否か

        // アカウント設定保存ボタン押下時
        if (isset($_POST['BeMoOve_admin_datas'])) {
            check_admin_referer('BeMoOve_Admin_Page');
            $opt = $_POST['BeMoOve_admin_datas'];
            update_option('BeMoOve_admin_datas', $opt);
			print('<div class="updated fade"><p style="font-weight: bold;">変更を保存しました。</p></div>');
        }

        wp_nonce_field('BeMoOve_Admin_Page');
        $opt = get_option('BeMoOve_admin_datas');
        $account_id = isset($opt['account_id']) ? $opt['account_id'] : null;
        $account_apiprekey = isset($opt['account_apiprekey']) ? $opt['account_apiprekey'] : null;

        if (!empty($opt['account_id']) && !empty($opt['account_apiprekey'])) {
        	$dt = date("YmdHis");
        	$account_apikey = md5($account_apiprekey . $dt);
        	$getaccount = file_get_contents('http://'.WP_BeMoOve_SUBDOMAIN.'.behls-lite.jp/account/get/'.$account_id.'/'.$account_apikey.'/'.$dt);
        	$accountxml = simplexml_load_string($getaccount);
        	$accountdata = json_decode(json_encode($accountxml), true);

        	$max_strage_capacity = $accountdata[getAccount][item][quota] / 1024 / 1024;
        	$max_strage_capacity = floor($max_strage_capacity * 10);
        	$max_strage_capacity = $max_strage_capacity / 10;

        	$dispstrage = $accountdata[getAccount][item][disk_used] / 1024 / 1024;
        	$dispstrage = floor($dispstrage * 10);
        	$dispstrage = $dispstrage / 10;

        	$used_rate = (0 < $max_strage_capacity) ? ($dispstrage * 100 / $max_strage_capacity) : 0;
        	$used_rate = floor($used_rate * 100);
        	$used_rate = $used_rate / 100;

        	$isAccountActivate = $accountdata[getAccount][item][activate] == 'T';
        }

        // 動画の同期処理
        if ($isAccountActivate && $_POST['sync_movies'] == '1') {
			$dt = date("YmdHis");
			$account_apikey = md5($account_apiprekey . $dt);
			$video_list_response = file_get_contents('http://'.WP_BeMoOve_SUBDOMAIN.'.behls-lite.jp/video/list/'.$account_id.'/'.$account_apikey.'/'.$dt);
			$video_list_xml = simplexml_load_string($video_list_response);

			// 動画が存在しない場合
			if ($video_list_xml->message->code == '102') {
				print('<div class="updated fade"><p style="font-weight: bold;">動画ファイルは存在しません。</p></div>');
            } else {

				$video_list_data = json_decode(json_encode($video_list_xml), true);
				$video_item_list = $video_list_data[listVideo];

				// APIで取得した動画一覧がWP側のDBにあれば更新、なければ追加を行う。
				// 重複したタグ名がAPIから取得された場合は、先頭に取得してきたもを優先し、後続のものは無視する
				global $wpdb;
				$wp_movie_names = $wpdb->get_results("SELECT name FROM {$this->table_name}");
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
					foreach ($wp_movie_names as $record) {
						if ($record->name == $vide_name) {
							$wp_has_target = true;
							break;
						}
					}

                	if ($wp_has_target) {
                    	// すでにWP側のDBにある場合
						$wpdb->update($this->table_name, $set_arr, array('name' => $vide_name));
                	} else {
                    	// WP側のDBにない場合
						$wpdb->insert($this->table_name, $set_arr);
                	}
                	$wpdb->show_errors();
            	}
            	print('<div class="updated fade"><p style="font-weight: bold;">このアカウントの動画を同期しました。</p></div>');
            }
        }
?>
        <div class="wrap">
        <h2>アカウント設定</h2>
        <form action="" method="post">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="inputtext3">account_id</label></th>
                    <td><input name="BeMoOve_admin_datas[account_id]" type="text" id="inputtext3" value="<?php echo $account_id ?>" class="regular-text" /></td>
                </tr>

                <tr valign="top">
                    <th scope="row"><label for="inputtext4">account_apiprekey</label></th>
                    <td><input name="BeMoOve_admin_datas[account_apiprekey]" type="text" id="inputtext4" value="<?php echo $account_apiprekey ?>" class="regular-text" /></td>
                </tr>
<?php
            if (!empty($opt['account_id']) && !empty($opt['account_apiprekey'])) {
                echo "<tr valign=\"top\"><th>ストレージ使用率</th><td>{$used_rate}%&nbsp;({$dispstrage}&nbsp;/&nbsp;{$max_strage_capacity}&nbsp;MB)</td></tr>";
            }
?>
            </table>
            <p class="submit"><input type="submit" name="Submit" class="button-primary" value="変更を保存" /></p>
        </form>

<?php
        if ($isAccountActivate === true) {
?>
        <br />
        <h2>動画の同期</h2>
        <form action="" method="post">
            <input type="hidden" name="sync_movies" value="1" />
            <p class="submit"><input type="submit" name="Submit" class="button-primary" value="アカウントの動画を同期" /></p>
        </form>
        </div>
<?php
        }
?>
<?php
    }

    /**
     * 新規追加メニューの画面表示を行う。
     */
    function BeMoOve_Input_Page() {
        global $wpdb;

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
            $subdomain = WP_BeMoOve_SUBDOMAIN;
            $is_detail = $_GET['fuo'] == 'detail';

            if ($video_tag) {
?>
                <form action="https://<?php echo $subdomain; ?>.behls-lite.jp/video/upload" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php print($account_id); ?>" />
                    <input type="hidden" name="apikey" value="<?php print($account_apikey); ?>" />
                    <input type="hidden" name="dt" value="<?php print($dt); ?>" />
                    <input type="hidden" name="tag" value="<?php print($video_tag); ?>" />
                    <input type="hidden" name="removeOrigin" value="T" />
                    <input type="hidden" name="redirectSuccess" value="<?php print(home_url()); ?>/wp-admin/admin.php?page=BeMoOve_new&m=success&n=<?php print(urlencode($video_tag));?>&t=<?php print($hash); ?>" />
                    <input type="hidden" name="redirectFailure" value="<?php print(home_url()); ?>/wp-admin/admin.php?page=BeMoOve_new&m=failure" />
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
                            <td><p class="bemoove_description">※アップできる容量は1GBまで / WMV/AVI/MPG/MPEG/MOV/M4V/3GP/3G2/FLV/MP4/TS/OGG/WEBM/MTS形式でアップ可能</p></td>
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
                            <td><p class="bemoove_description">※変換後の画面サイズを指定します。縦横の比率は元の動画と合わせてください。</p></td>
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
                            <td><p class="bemoove_description">※変換後の動画フレームレートを指定する。 / デフォルト値（29.97）</p></td>
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
                            <td><p class="bemoove_description">※変換後の動画ビットレートを指定する。 /デフォルト値（1024）</p></td>
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
                            <td><p class="bemoove_description">※変換後の音声サンプリングレートを指定する。 / デフォルト値（44100）</p></td>
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
                            <td><p class="bemoove_description">※変換後の音声ビットレートを指定する。 / デフォルト値（128）</p></td>
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
                            <td><p class="bemoove_description">※変換後の音声チャンネルを指定する。 / デフォルト値（2）</p></td>
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
                            <td><p class="bemoove_description">※変換後のプロファイルを指定する。 / デフォルト値（baseline）</p></td>
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
                            <td><p class="bemoove_description">※変換後のレベルを指定する。 / デフォルト値（30）</p></td>
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
                            <td><p class="bemoove_description">※滑らかに動画を再生したい方は「高」をお選びください。</p></td>
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
                            <td><p class="bemoove_description">※サムネイルを作成する時間を指定する。 / デフォルト値（5秒）</p></td>
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
				, 'flag' => '1'
			);

            // 同名のタグがすでにあれば削除した後に追加
            $same_name_records = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE name = %s", $name));

            if ($same_name_records && 0 < count($same_name_records)) {
				$opt = get_option('BeMoOve_admin_datas');
				$account_id = isset($opt['account_id']) ? $opt['account_id']: null;
				$account_apiprekey = isset($opt['account_apiprekey']) ? $opt['account_apiprekey']: null;
				$dt = date("YmdHis");
				$account_apikey = md5($account_apiprekey . $dt);
                foreach ($same_name_records as $snr) {
					// BeHLs側の古いファイルを削除
					$buf = file_get_contents("http://".WP_BeMoOve_SUBDOMAIN.".behls-lite.jp/video/remove/{$account_id}/{$account_apikey}/{$dt}/{$snr->video_hash}");
                }
                $wpdb->query($wpdb->prepare("DELETE FROM $this->table_name WHERE name = %s", $name));
            }

            $wpdb->insert($this->table_name, $set_arr);

            $wpdb->show_errors();
?>
<div class="wrap">
    <h2>動画のアップロード</h2>
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
                <td><input name="movie_name" type="text" id="inputtext" pattern="^[0-9A-Za-z]+$" value="" placeholder="半角英数で入力してください(80文字まで)" class="regular-text" maxlength="80"/></td>
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
        if ($_GET['m'] == 'details') {
            $opt = get_option('BeMoOve_admin_datas');
            $account_id = isset($opt['account_id']) ? $opt['account_id']: null;
            $account_apiprekey = isset($opt['account_apiprekey']) ? $opt['account_apiprekey']: null;
            $dt = date("YmdHis");
            $account_apikey = md5($account_apiprekey . $dt);
            $hash_name = $_GET['hash'];
            $override_thumbnail_file = $_GET['otf'];
?>
            <div class="wrap">
            <h2>動画詳細 <a href="admin.php?page=BeMoOve_movies_list" class="add-new-h2">戻る</a></h2>
<?php
            global $wpdb;
            $table_name = $this->table_name;

            if ($override_thumbnail_file) {
                // サムネファイルのURL編集の場合
                if ($override_thumbnail_file == 'default') {
					$set_arr = array('override_thumbnail_file' => null);
					$wpdb->update($table_name, $set_arr, array('video_hash' => $hash_name));
					print("サムネイルファイルをご登録時のものに戻しました。");
                } else {
					$set_arr = array('override_thumbnail_file' => $override_thumbnail_file);
					$wpdb->update($table_name, $set_arr, array('video_hash' => $hash_name));
					print("サムネイルファイルを変更しました。");
                }
            }

            $get_id = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE video_hash = %s", $hash_name));
            $beMoOveTag = new BeMoOveTag($get_id[0]);
            print($beMoOveTag->getEmbedSrc(WP_BeMoOve_SUBDOMAIN, $account_id, true));
            print("<br />");

            $buf = file_get_contents('http://'.WP_BeMoOve_SUBDOMAIN.'.behls-lite.jp/video/get/'.$account_id.'/'.$account_apikey.'/'.$dt.'/'.$hash_name);
            $xml = simplexml_load_string($buf);
            $data = json_decode(json_encode($xml), true);

            print('
                <table cellpadding="5" cellspacing="1" bgcolor="#bbbbbb">
                    <tr><td bgcolor="#ccc" colspan="2">VIDEO</td></tr>
                    <tr><td bgcolor="#ccc" width="90">tag</td><td bgcolor="#fff" width="300">'.$data['getVideo']['item']['video']['tag'].'</td></tr>
                    <tr><td bgcolor="#ccc" width="90">hash</td><td bgcolor="#fff" width="300">'.$data['getVideo']['item']['video']['hash'].'</td></tr>
                    <tr><td bgcolor="#ccc" width="90">file_tag</td><td bgcolor="#fff" width="300">'.$data['getVideo']['item']['video']['file_tag'].'</td></tr>
                    <tr><td bgcolor="#ccc" width="90">file_hash</td><td bgcolor="#fff" width="300">'.$data['getVideo']['item']['video']['file_hash'].'</td></tr>
                    <tr><td bgcolor="#ccc" width="90">file_path</td><td bgcolor="#fff" width="300">'.$data['getVideo']['item']['video']['file_path'].'</td></tr>
                    <tr><td bgcolor="#ccc" width="90">size</td><td bgcolor="#fff" width="300">'.$data['getVideo']['item']['video']['size'].'</td></tr>
                    <tr><td bgcolor="#ccc" width="90">duration</td><td bgcolor="#fff" width="300">'.$data['getVideo']['item']['video']['duration'].'</td></tr>
                    <tr><td bgcolor="#ccc" width="90">convert_time</td><td bgcolor="#fff" width="300">'.$data['getVideo']['item']['video']['convert_time'].'</td></tr>
                    <tr><td bgcolor="#ccc" width="90">s</td><td bgcolor="#fff" width="300">'.$data['getVideo']['item']['video']['s'].'</td></tr>
                    <tr><td bgcolor="#ccc" width="90">aspect</td><td bgcolor="#fff" width="300">'.$data['getVideo']['item']['video']['aspect'].'</td></tr>
                    <tr><td bgcolor="#ccc" width="90">r</td><td bgcolor="#fff" width="300">'.$data['getVideo']['item']['video']['r'].'</td></tr>
                    <tr><td bgcolor="#ccc" width="90">b</td><td bgcolor="#fff" width="300">'.$data['getVideo']['item']['video']['b'].'</td></tr>
                    <tr><td bgcolor="#ccc" width="90">ar</td><td bgcolor="#fff" width="300">'.$data['getVideo']['item']['video']['ar'].'</td></tr>
                    <tr><td bgcolor="#ccc" width="90">ab</td><td bgcolor="#fff" width="300">'.$data['getVideo']['item']['video']['ab'].'</td></tr>
                    <tr><td bgcolor="#ccc" width="90">ac</td><td bgcolor="#fff" width="300">'.$data['getVideo']['item']['video']['ac'].'</td></tr>
                    <tr><td bgcolor="#ccc" width="90">profile</td><td bgcolor="#fff" width="300">'.$data['getVideo']['item']['video']['profile'].'</td></tr>
                    <tr><td bgcolor="#ccc" width="90">level</td><td bgcolor="#fff" width="300">'.$data['getVideo']['item']['video']['level'].'</td></tr>
                    <tr><td bgcolor="#ccc" width="90">created_at</td><td bgcolor="#fff" width="300">'.$data['getVideo']['item']['video']['created_at'].'</td></tr>
                    <tr><td bgcolor="#ccc" colspan="2">THUMBNAIL</td></tr>
                    <tr><td bgcolor="#ccc" width="90">file_path</td><td bgcolor="#fff" width="300">'
                      . ($beMoOveTag->isThumbnailFileOverridden() ? $beMoOveTag->getDispThumbnailFile() :$data['getVideo']['item']['thumbnail']['file_path']) . '</td></tr>
                    <tr><td bgcolor="#ccc" width="90"></td><td bgcolor="#fff" width="300" style="text-align: right;">'
                      . ($beMoOveTag->isThumbnailFileOverridden() ? '<a id="link_thumbnail_default" href="admin.php?page=BeMoOve_movies_list&m=details&hash='. $hash_name .'&otf=default">サムネイルを元に戻す</a>&nbsp;&nbsp;&nbsp;&nbsp;' : '')
                      . '<a id="link_thumbnail_edit" href="admin.php?page=BeMoOve_movies_list&m=details&hash='. $hash_name .'&otf=">サムネイルを変更する</a></td></tr>
                </table>
            ');
?>
			</div>
<?php
        // 削除画面の場合
        } elseif ($_GET['m'] == 'delete') {
            global $wpdb;
            $opt = get_option('BeMoOve_admin_datas');
            $account_id = isset($opt['account_id']) ? $opt['account_id']: null;
            $account_apiprekey = isset($opt['account_apiprekey']) ? $opt['account_apiprekey']: null;
            $dt = date("YmdHis");
            $account_apikey = md5($account_apiprekey . $dt);

            $buf = file_get_contents('http://'.WP_BeMoOve_SUBDOMAIN.'.behls-lite.jp/video/remove/'.$account_id.'/'.$account_apikey.'/'.$dt.'/'.$_GET['hash']);
            $xml = simplexml_load_string($buf);
            $data = json_decode(json_encode($xml), true);

            $wpdb->query($wpdb->prepare("DELETE FROM $this->table_name WHERE video_hash = %s", $_GET['hash']));
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
            global $wpdb;

            $offset = 0;
            if (is_numeric($_GET["s"])) {
                $offset = $_GET["s"];
            }
            $get_list = $wpdb->get_results(
            	$wpdb->prepare(
            		"SELECT * FROM "
            			. $this->table_name
            			. " order by movie_id desc limit %d, %d", $offset, WP_BeMoOve_ITEMS_LIMIT)
            );
            $page_area = $this -> get_pagination();
?>
            <table><tr><td><?php print($page_area); ?></td></tr></table>
<?php
			// 動画情報登録処理を行い、その後表示処理を行う。
            $opt = get_option('BeMoOve_admin_datas');
            $account_id = isset($opt['account_id']) ? $opt['account_id']: null;
            $account_apiprekey = isset($opt['account_apiprekey']) ? $opt['account_apiprekey']: null;
            $dt = date("YmdHis");
            $account_apikey = md5($account_apiprekey . $dt);

            foreach ($get_list as $key => $val) {

                if ($val) {
                    // 既にデータがあったら更新しない
                    if ($val->flag == '0') continue;

                    $buf = file_get_contents('http://'.WP_BeMoOve_SUBDOMAIN.'.behls-lite.jp/video/get/'.$account_id.'/'.$account_apikey.'/'.$dt.'/'.$val->name);
                    if (empty($buf)) continue;

                    $xml = simplexml_load_string($buf);

                    // getできなかったら更新しない
                    if ($xml->message->code == '102') continue;

                    $data = json_decode(json_encode($xml), true);
                    $size = explode("x", $data['getVideo']['item']['video']['s']);

                    // 数値が取得できなかったら更新しない
                    // 新規登録直後には##video_s##という文字列で返却される場合がある
                    if (!is_numeric($size[0])) continue;

                    $set_arr = array(
                        'video_hash' => $data['getVideo']['item']['video']['hash']
                        , 'video_file' => $data['getVideo']['item']['video']['file_hash']
                        , 'video_width' => $size[0]
                        , 'video_height' => $size[1]
                        , 'video_time' => $data['getVideo']['item']['video']['duration']
                        , 'thumbnail_hash' => $data['getVideo']['item']['thumbnail']['hash']
                        , 'thumbnail_file' => $data['getVideo']['item']['thumbnail']['file_hash']
                        , 'callback_date' => date('Y-m-d H:i:s')
                        , 'flag' => '0'
                    );

                    $table_name = $this->table_name;
                    $wpdb->update($table_name, $set_arr, array('name' => $val->name));
                    $wpdb->show_errors();
                }
            }

            // 動画一覧表示処理
            foreach ($get_list as $key => $val) {
                $beMoOveTag = new BeMoOveTag($val);
                print($beMoOveTag->createListItemInfo(WP_BeMoOve_SUBDOMAIN, $account_id));
            }
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
		global $wpdb;
		$opt = get_option('BeMoOve_admin_datas');
		$account_id = isset($opt['account_id']) ? $opt['account_id']: null;
		$account_apiprekey = isset($opt['account_apiprekey']) ? $opt['account_apiprekey']: null;
		$dt = date("YmdHis");
		$account_apikey = md5($account_apiprekey . $dt);
		$tagName = $_POST["tag_name"];

		$same_name_records = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE name = %s", $tagName));
		if (!$same_name_records && count($same_name_records) < 1) die('error');

        $same_name_record = $same_name_records[0];

        // 既にデータがあったら更新せずにhtmlを作成して返却
        if ($same_name_record->flag == '0') {
			$bemooveTag = new BeMoOveTag($same_name_record);
			die($bemooveTag->createListItemInfo(WP_BeMoOve_SUBDOMAIN, $account_id));
		}

        $buf = file_get_contents("http://" . WP_BeMoOve_SUBDOMAIN . ".behls-lite.jp/video/get/{$account_id}/{$account_apikey}/{$dt}/{$tagName}");
        if (empty($buf)) die('error');

        $xml = simplexml_load_string($buf);
        if ($xml->message->code == '102') {
			die('error');
		}

        $data = json_decode(json_encode($xml), true);
        $size = explode("x", $data['getVideo']['item']['video']['s']);

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
        $table_name = $this->table_name;
        $wpdb->update($table_name, $set_arr, array('name' => $tagName));
        $wpdb->show_errors();

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
		die($bemooveTag->createListItemInfo(WP_BeMoOve_SUBDOMAIN, $account_id));
    }

    function cmt_activate($table_name) {
        $cmt_db_version = '1.00';
        $installed_ver = get_option('cmt_meta_version');
        // テーブルのバージョンが違ったら作成
        if ($installed_ver != $cmt_db_version) {
            $sql = "CREATE TABLE " . $table_name . " (
                movie_id int(11) NOT NULL AUTO_INCREMENT,
                name varchar(100) NOT NULL,
                hash_name varchar(50) NOT NULL,
                video_hash varchar(50) NOT NULL,
                video_file varchar(255) NOT NULL,
                video_width int(5) NOT NULL,
                video_height int(5) NOT NULL,
                video_time varchar(20) NOT NULL,
                thumbnail_hash varchar(50) NOT NULL,
                thumbnail_file varchar(255) NOT NULL,
                create_date timestamp on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                callback_date timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
                redirectSuccess_code int(10) NOT NULL DEFAULT '0',
                override_thumbnail_file varchar(255) NOT NULL,
                flag int(1) NOT NULL DEFAULT '1',
                UNIQUE KEY movie_id (movie_id)
            )
            CHARACTER SET 'utf8';";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            update_option('cmt_meta_version', $cmt_db_version);
        }
    }

    function get_pagination() {
        $m_hr = WP_BeMoOve_ITEMS_LIMIT; //表示最大数
        global $wpdb;

        if ($_GET["s"] == "") $_GET["s"] = 0;

        $max = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . $this->table_name, 0));
        $start = $_GET['s'];
        $page = $max / $m_hr;
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
            <h2>○ブログに動画を貼り付けるまで</h2>
            <div class="help_content">
            <ul>
            <li>
    １、<a href="http://dev.behls.jp/" target="_blank">＜登録用URL＞</a>からwordpress用のアカウントを登録して、account_idとaccount_apiprekeyを入手<br />
    ※最短1時間～お時間を頂くことが御座います。<br />
    ※容量1GBまで無料です。
            </li>
            <li>
    ２、wordpressの管理画面からプラグインに『WP-BeMoOvePlugin』を登録し、有効化する<br />
    ※wordpressのメニューに『BeMoOve』の項目が追加されます。
            </li>
            <li>
    ３、『アカウント設定画面』を開き、<br />
    『account_id』と『account_apiprekey』を入力し、『変更を保存』ボタンを押す
            </li>
            <li>
    ４、『新規追加』を押し、動画名を登録<br />
    ※同一の動画名の動画が複数ある場合、最新の動画が反映されます。
            </li>
            <li>
    ５、各種オプションを指定して動画をアップロード<br />
    ※縦横のサイズ比率は元々の動画と同じにしてください。<br />
             <li>
    ６、動画一覧(BeMoOveをクリック)から貼り付け用タグをコピーして記事に貼り付け<br />
    ※貼り付け時に縦横サイズの比率を指定することもできます。<br />
    例）[<?php print(WP_BeMoOve_TAG_ATTR_NAME) ?>="sample"]の場合<br />
    [<?php print(WP_BeMoOve_TAG_ATTR_NAME) ?>="sample(320, 240)"]と記述することで、<br />320×240のサイズで表示することができます。<br />
    ※動画詳細画面よりサムネイル画像を変更することも可能です。<br />
    サムネイル画像のURLを指定することで、その画像をサムネイルとしてご使用いただけます。<br />
    メディアファイルのアップロード等をご活用ください。
            </li>
            </ul>
            </div>

            <h2>○動画の削除について</h2>
            <div class="help_content">
            『動画一覧』画面にて、削除したい動画の右下の『削除』ボタンを押してください。
            </div>


            <h2>○変換が終わらない</h2>
            <div class="help_content">
              容量の大きいファイルなどアップロードされますと、
              時間がかかる場合が御座います。
              しばらくお待ちください。
            </div>
            </div>
<?php
    }

}
