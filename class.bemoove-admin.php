<?php
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

        add_action('save_post', array($this, 'save_meta'));
        add_action('delete_post', array($this, 'dalete_meta'));
    }

    function add_pages() {
        add_menu_page('BeMoOve','BeMoOve', 'level_8', 'BeMoOve_movies_list', array($this, 'BeMoOve_Movies_List_Page'), '', NULL);
        add_submenu_page('BeMoOve_movies_list', '新規追加', '新規追加', 'level_8', 'BeMoOve_new', array($this, 'BeMoOve_Input_Page'));
        add_submenu_page('BeMoOve_movies_list', 'アカウント設定', 'アカウント設定', 'level_8', 'BeMoOve_setting', array($this, 'BeMoOve_Admin_Page'));
        add_submenu_page('BeMoOve_movies_list', '使い方', '使い方', 'level_8', 'BeMoOve_help', array($this, 'BeMoOve_Help_Page'));
    }

    function BeMoOve_Help_Page() {
?>
        <div class="wrap">
        <h2>○ブログに動画を貼り付けるまで</h2>
        <div class="help_content">
        <ul>
        <li>
１、<a href="http://dev.behls.jp/" target="_blank">＜登録用URL＞</a>からwordpress用のアカウントを登録して、account_idとaccount_apiprekeyを入手<br />
※最短1時間～お時間を頂くことが御座います<br />
※容量1GBまで無料です
        </li>
        <li>
２、wordpressの管理画面からプラグインに『WP-BeMoOvePlugin』を登録し、有効化する<br />
※wordpressのメニューに『BeMoOve』の項目が追加されます
        </li>
        <li>
３、『アカウント設定画面』を開き、<br />
『account_id』と『account_apiprekey』を入力し、『変更を保存』ボタンを押す
        </li>
        <li>
４、『新規追加』を押し、動画名を登録<br />
※同一の動画名の動画が複数ある場合、最新の動画が反映されます
        </li>
        <li>
５、各種オプションを指定して動画をアップロード<br />
※縦横のサイズ比率は元々の動画と同じにしてください<br />
         <li>
６、動画一覧(BeMoOveをクリック)から貼り付け用タグをコピーして記事に貼り付け
        </li>
        </ul>
        </div>

        <h2>○動画の削除について</h2>
        <div class="help_content">
        『動画一覧』画面にて、削除したい動画の右下の『削除』ボタンを押してください
        </div>


        <h2>○変換が終わらない</h2>
        <div class="help_content">
          容量の大きいファイルなどアップロードされますと、
          時間がかかる場合が御座います。
          しばらくお待ちください。
        <div>
        </div>
<?php
    }


    function BeMoOve_Admin_Page() {
        if (isset($_POST['BeMoOve_admin_datas'])) {
            check_admin_referer('BeMoOve_Admin_Page');
            $opt = $_POST['BeMoOve_admin_datas'];
            update_option('BeMoOve_admin_datas', $opt);
?>
            <div class="updated fade"><p><strong><?php _e('変更を保存しました'); ?></strong></p></div>
<?php
        }
?>
        <div class="wrap">
        <h2>アカウント設定</h2>
        <form action="" method="post">
<?php
            wp_nonce_field('BeMoOve_Admin_Page');
            $opt = get_option('BeMoOve_admin_datas');
            $account_id = isset($opt['account_id']) ? $opt['account_id']: null;
            $account_apiprekey = isset($opt['account_apiprekey']) ? $opt['account_apiprekey']: null;

        if (!empty($opt['account_id']) && !empty($opt['account_apiprekey'])) {
            $dt = date("YmdHis");
            $account_apikey = md5($account_apiprekey . $dt);
            $getaccount = file_get_contents('http://'.WP_BeMoOve_SUBDOMAIN.'.behls-lite.jp/account/get/'.$account_id.'/'.$account_apikey.'/'.$dt);
            $accountxml = simplexml_load_string($getaccount);
            $accountdata = json_decode(json_encode($accountxml), true);
            $dispstrage = $accountdata[getAccount][item][disk_used]/1024/1024;
            $dispstrage = floor($dispstrage*10);
            $dispstrage = $dispstrage/10;
        }
?>
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
                echo '<tr valign="top">
                          <th>ストレージ使用量</th>
                          <td>' . $dispstrage . 'MB</td>
                      </tr>';
            }
?>
            </table>
            <p class="submit"><input type="submit" name="Submit" class="button-primary" value="変更を保存" /></p>
        </form>
        </div>
<?php
    }



    function BeMoOve_Input_Page() {
        global $wpdb;
        if($_GET['m'] == 'file_upload'){
?>
            <div class="wrap">
            <h2>動画アップロード</h2>

<?php
            $opt = get_option('BeMoOve_admin_datas');
            $account_id = isset($opt['account_id']) ? $opt['account_id']: null;
            $account_apiprekey = isset($opt['account_apiprekey']) ? $opt['account_apiprekey']: null;
            $dt = date("YmdHis");
            $account_apikey = md5($account_apiprekey . $dt);
            $hash = md5($dt.rand());
            $video_tag = $_POST['movie_name'];
            $subdomain = WP_BeMoOve_SUBDOMAIN;

            if($_POST['movie_name']){
?>
                <form action="https://<?php echo $subdomain; ?>.behls-lite.jp/video/upload" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php print($account_id); ?>" />
                    <input type="hidden" name="apikey" value="<?php print($account_apikey); ?>" />
                    <input type="hidden" name="dt" value="<?php print($dt); ?>" />
                    <input type="hidden" name="tag" value="<?php print($video_tag); ?>" />
                    <input type="hidden" name="removeOrigin" value="T" />
                    <input type="hidden" name="redirectSuccess" value="<?php print(home_url()); ?>/wp-admin/admin.php?page=BeMoOve_new&m=success&n=<?php print(urlencode($_POST['movie_name']));?>&t=<?php print($hash); ?>" />
                    <input type="hidden" name="redirectFailure" value="<?php print(home_url()); ?>/wp-admin/admin.php?page=BeMoOve_new&m=failure" />
                    <input type="hidden" name="preset" value="veryfast" />

                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row"><label for="inputtext">アップロード動画名</label></th>
                            <td><?php print($_POST['movie_name']); ?></td>
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
                            <td><h2 class="bemoove_title">画面サイズ</td>
                        </tr>
                        <tr>
                            <td><select name="s">
                                <option value="128x96">128&#215;96</option>
                                <option value="160x120">160&#215;120</option>
                                <option value="320x240">320&#215;240</option>
                                <option value="480x360">480&#215;360</option>
                                <option value="640x480">640&#215;480</option>
                                <option value="768x576">768&#215;576</option>
                                <option value="800x600">800&#215;600</option>
                                <option value="1024x768">1024&#215;768</option>
                                <option value="1280x960">1280&#215;960</option>
                                <option value="1600x1200">1600&#215;1200</option>
                                <option value="1920x1440">1920&#215;1440</option>
                                <option value="128x72">128&#215;72</option>
                                <option value="160x90">160&#215;90</option>
                                <option value="320x180" selected>320&#215;180</option>
                                <option value="480x270">480&#215;270</option>
                                <option value="640x360">640&#215;360</option>
                                <option value="768x432">768&#215;432</option>
                                <option value="800x450">800&#215;450</option>
                                <option value="1024x576">1024&#215;576</option>
                                <option value="1280x720">1280&#215;720</option>
                                <option value="1600x900">1600&#215;900</option>
                                <option value="1920x1080">1920&#215;1080</option>
                            </select></td>
                        </tr>
                        <tr>
                            <td><p class="bemoove_description">※変換後の画面サイズを指定します。縦横の比率は元の動画と合わせてください。</p></td>
                        </tr>

                        <tr>
                            <td><h2 class="bemoove_title">フレームレート</td>
                        </tr>
                        <tr>
                            <td><select name="r">
                                <option value="15">15</option>
                                <option value="24">24</option>
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
                            <td><h2 class="bemoove_title">動画ビットレート</td>
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
                            <td><h2 class="bemoove_title">音声サンプリングレート</td>
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
<?php
            }else{
?>
アップロード動画名が空白です。
<?php
            }
?>
            </div>
<?php
        }elseif($_GET['m'] == 'success'){
            $name = isset($_GET['n']) ? $_GET['n'] : null;
            $hash_name = isset($_GET['t']) ? $_GET['t'] : null;
            $code = isset($_GET['code']) ? $_GET['code'] : null;
            //保存するために配列にする
            $set_arr = array(
                'name' => $name,
                'hash_name' => $hash_name,
                'redirectSuccess_code' => $code,
                'flag' => '1',
            );
            $wpdb->insert( $this->table_name, $set_arr);
            $wpdb->show_errors();
?>
<div class="wrap">
    <h2>動画のアップロード</h2>
    動画のアップロードを完了しました。現在、動画変換処理中となりますので、しばらくお待ちください。<br />
    <a href="admin.php?page=BeMoOve_movies_list">動画一覧へ</a>
</div>
<?php
        }elseif($_GET['m'] == 'failure'){
?>
<div class="wrap">
<h2>動画のアップロード</h2>
動画投稿に失敗しました。<br />
<a href="admin.php?page=BeMoOve_movies_list">動画一覧へ</a>
</div>
<?php
        }else{
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



    function BeMoOve_Movies_List_Page() {
        if($_GET['m'] == 'details'){
            $opt = get_option('BeMoOve_admin_datas');
            $account_id = isset($opt['account_id']) ? $opt['account_id']: null;
            $account_apiprekey = isset($opt['account_apiprekey']) ? $opt['account_apiprekey']: null;
            $dt = date("YmdHis");
            $account_apikey = md5($account_apiprekey . $dt);
            $hash_name = $_GET['hash']
?>
            <div class="wrap">
            <h2>動画詳細 <a href="<?php print($_SERVER["HTTP_REFERER"]); ?>" class="add-new-h2">戻る</a></h2>
<?php
            global $wpdb;

            $table_name=$wpdb->prefix.'movie_meta';
            $get_id = $wpdb->get_results($wpdb->prepare("
                SELECT *
                FROM $table_name
                WHERE video_hash = '%s'
                ", $hash_name));
            print('
                <script type="text/javascript">
                var isAndroid = false;
                var isIOS     = false;
                var ua = navigator.userAgent.toLowerCase();
                if (ua.match(/Android/i)) var isAndroid = true;
                if (ua.match(/iP(hone|ad|od)/i)) var isIOS = true;
                function $(e) { return document.getElementById(e); }
                function addEvent(event, func, obj) {
                    var obj = obj || window;
                    if ( typeof obj.addEventListener != "undefined" ) {
                        obj.addEventListener( event, func, false );
                    } else if ( typeof obj.attachEvent != "undefined" ) {
                        event = "on" + event;
                        obj.attachEvent( event, func );
                    }
                }
                </script>
                <script type="text/javascript" src="https://'.WP_BeMoOve_SUBDOMAIN.'.behls-lite.jp/js/jwplayer.js"></script>
                <script type="text/javascript">jwplayer.key="GExaQ71lyaswjxyW6fBfmJnwYHwXQ9VI1SSpWNtsQo4=";</script>
                <div id="myElement">Loading the player...</div>
                <script type="text/javascript">
                if (!isAndroid && !isIOS) {
                    jwplayer("myElement").setup({
                    file:   "https://'.WP_BeMoOve_SUBDOMAIN.'.behls-lite.jp/media/video/'.$account_id.'/'.$get_id[0]->video_hash.'.m3u8",
                    image:  "https://'.WP_BeMoOve_SUBDOMAIN.'.behls-lite.jp/media/thumbnail/'.$account_id.'/'.$get_id[0]->thumbnail_hash.'",
                    width:  "'.$get_id[0]->video_width.'",
                    height: "'.$get_id[0]->video_height.'",
                    stretching: "exactfit",
                    controls: true,
                    repeat: false,
                    abouttext: "'.$abouttext.'",
                    aboutlink: "'.$aboutlink.'"
                    });
                } else {
                    $("myElement").innerHTML = ""
                        + "<video id=myVideo"
                        + " src=\'https://'.WP_BeMoOve_SUBDOMAIN.'.behls-lite.jp/media/video/'.$account_id.'/'.$get_id[0]->video_hash.'.m3u8\' "
                        + " poster=\'https://'.WP_BeMoOve_SUBDOMAIN.'.behls-lite.jp/media/thumbnail/'.$account_id.'/'.$get_id[0]->thumbnail_hash.'\' "
                        + " width=\''.$get_id[0]->video_width.'\' height=\''.$get_id[0]->video_height.'\' "
                        + " controls>"
                        + " </video>";
                }
                </script>
                <br>
            ');

            $buf = file_get_contents('http://'.WP_BeMoOve_SUBDOMAIN.'.behls-lite.jp/video/get/'.$account_id.'/'.$account_apikey.'/'.$dt.'/'.$_GET['hash']);
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
                    <tr><td bgcolor="#ccc" width="90">hash</td><td bgcolor="#fff" width="300">'.$data['getVideo']['item']['thumbnail']['hash'].'</td></tr>
                    <tr><td bgcolor="#ccc" width="90">file_tag</td><td bgcolor="#fff" width="300">'.$data['getVideo']['item']['thumbnail']['file_tag'].'</td></tr>
                    <tr><td bgcolor="#ccc" width="90">file_hash</td><td bgcolor="#fff" width="300">'.$data['getVideo']['item']['thumbnail']['file_hash'].'</td></tr>
                    <tr><td bgcolor="#ccc" width="90">file_path</td><td bgcolor="#fff" width="300">'.$data['getVideo']['item']['thumbnail']['file_path'].'</td></tr>
                    <tr><td bgcolor="#ccc" width="90">size</td><td bgcolor="#fff" width="300">'.$data['getVideo']['item']['thumbnail']['size'].'</td></tr>
                    <tr><td bgcolor="#ccc" width="90">duration</td><td bgcolor="#fff" width="300">'.$data['getVideo']['item']['thumbnail']['duration'].'</td></tr>
                    <tr><td bgcolor="#ccc" width="90">s</td><td bgcolor="#fff" width="300">'.$data['getVideo']['item']['thumbnail']['s'].'</td></tr>
                    <tr><td bgcolor="#ccc" width="90">convert_time</td><td bgcolor="#fff" width="300">'.$data['getVideo']['item']['thumbnail']['convert_time'].'</td></tr>
                    <tr><td bgcolor="#ccc" width="90">created_at</td><td bgcolor="#fff" width="300">'.$data['getVideo']['item']['thumbnail']['created_at'].'</td></tr>
                </table>
            ');

        }elseif($_GET['m'] == 'delete'){
            global $wpdb;
            $opt = get_option('BeMoOve_admin_datas');
            $account_id = isset($opt['account_id']) ? $opt['account_id']: null;
            $account_apiprekey = isset($opt['account_apiprekey']) ? $opt['account_apiprekey']: null;
            $dt = date("YmdHis");
            $account_apikey = md5($account_apiprekey . $dt);

            $buf = file_get_contents('http://'.WP_BeMoOve_SUBDOMAIN.'.behls-lite.jp/video/remove/'.$account_id.'/'.$account_apikey.'/'.$dt.'/'.$_GET['hash']);
            $xml = simplexml_load_string($buf);
            $data = json_decode(json_encode($xml), true);

            $wpdb->query( $wpdb->prepare("DELETE FROM $this->table_name WHERE video_hash = %s", $_GET['hash']));
?>
            <div class="wrap">
            <h2>動画削除</h2>

            削除しました。<br />
            <a href="admin.php?page=BeMoOve_movies_list">動画一覧へ</a>
<?php
        }else{
?>
            <div class="wrap">
            <h2>動画一覧 <a href="admin.php?page=BeMoOve_new" class="add-new-h2">新規追加</a></h2>
<?php
            global $wpdb;
            if($_GET["s"] == ""){$_GET["s"] = 0;}
            $get_list = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM
                " . $this->table_name . " order by movie_id desc limit " . $_GET["s"].", ".WP_BeMoOve_ITEMS_LIMIT, 0)
            );
            $page_area = $this -> get_pagination();
?>
            <table><tr><td><?php print($page_area); ?></td></tr></table>
<?php
//ここから動画情報登録----------
            foreach ($get_list as $key => $val) {
                if($val){
                    $table_name=$wpdb->prefix.'movie_meta';
                    $hash_name = $val->name;
                    $get_id = $wpdb->get_results($wpdb->prepare("
                        SELECT *
                        FROM $table_name
                        WHERE name = '%s'
                        ", $hash_name));

                    //レコードがなかったら更新しない
                    if ($get_id) {

                        //既にデータがあったら更新しない
                        if ($val->flag == '0') continue;

                        $opt = get_option('BeMoOve_admin_datas');
                        $account_id = isset($opt['account_id']) ? $opt['account_id']: null;
                        $account_apiprekey = isset($opt['account_apiprekey']) ? $opt['account_apiprekey']: null;
                        $dt = date("YmdHis");
                        $account_apikey = md5($account_apiprekey . $dt);

                        $buf = file_get_contents('http://'.WP_BeMoOve_SUBDOMAIN.'.behls-lite.jp/video/get/'.$account_id.'/'.$account_apikey.'/'.$dt.'/'.$val->name);
                        if (empty($buf)) continue;

                        $xml = simplexml_load_string($buf);

                        //getできなかったら更新しない
                        if ($xml->message->code == '102') continue;

                        $data = json_decode(json_encode($xml), true);
                        $size = explode("x", $data['getVideo']['item']['video']['s']);

                        //数値が取得できなかったら更新しない
                        $needle = '##';
                        $light = strpos($buf, $needle);

                        if ($light) continue;

                        $set_arr = array(
                        'video_hash' => $data['getVideo']['item']['video']['hash'],
                        'video_file' => $data['getVideo']['item']['video']['file_hash'],
                        'video_width' => $size[0],
                        'video_height' => $size[1],
                        'video_time' => $data['getVideo']['item']['video']['duration'],
                        'thumbnail_hash' => $data['getVideo']['item']['thumbnail']['hash'],
                        'thumbnail_file' => $data['getVideo']['item']['thumbnail']['file_hash'],
                        'callback_date' => date('Y-m-d H:i:s'),
                        'flag' => '0'
                        );
                        $wpdb->update( $table_name, $set_arr, array('name' => $hash_name));
                    }
                    $wpdb->show_errors();
                }else{
                    print('ERROR');
                }

            }
//動画情報登録ここまで----------
//動画情報表示ここから----------
            foreach ($get_list as $key => $val) {
                if($val->flag == '0'){
                    print('
                    <table cellpadding="5" cellspacing="1" bgcolor="#bbbbbb">
                    <tr>
                    <td bgcolor="#fff" width="130" rowspan="7" style="text-align:center;">
                    <a href="admin.php?page=BeMoOve_movies_list&m=details&hash='.$val->video_hash.'""><img src="'.$val->thumbnail_file.'" width="120"></a>
                    </td>
                    <td valign="top" bgcolor="#ccc" width="90">貼り付け用タグ</td>
                    <td valign="top" bgcolor="#fff" width="300">
                        <input type="text" readonly="readonly" value=\'[bemoove_tag="' . $val->name . '"]\'" />
                    </td>
                    </tr>
                    <tr>
                    <td valign="top" bgcolor="#ccc">ソース</td>
                    <td valign="top" bgcolor="#fff"><textarea rows="3" style="width:100%;">' . $this -> create_BeMoOve_src($val->name) . '</textarea></td>
                    </tr>
                    <tr>
                    <td valign="top" bgcolor="#ccc">ビデオサイズ</td>
                    <td valign="top" bgcolor="#fff">'.$val->video_width.'x'.$val->video_height.'</td>
                    </tr>
                    <tr>
                    <td valign="top" bgcolor="#ccc">再生時間</td>
                    <td valign="top" bgcolor="#fff">'.$val->video_time.'</td>
                    </tr>
                    <tr>
                    <td valign="top" bgcolor="#ccc" colspan="2" align="right">
                    <input type="button" value="削除" onclick="var res = confirm(\'本当に削除してもよろしいですか？\');if( res == true ) {location.href = \'admin.php?page=BeMoOve_movies_list&m=delete&hash='.$val->video_hash.'\'}else{}" />
                    </td>
                    </tr>
                    </table><br />');

                }else{

                    print('

                    <table cellpadding="5" cellspacing="1" bgcolor="#bbbbbb">
                    <tr>
                    <td bgcolor="#fff" width="130" rowspan="7" style="text-align:center;">
                    <img src="'.WP_BeMoOve__PLUGIN_URL.'/images/noimage.jpg" width="120"></a>
                    </td>
                    <td valign="top" bgcolor="#ccc" width="90">貼り付け用タグ</td>
                    <td valign="top" bgcolor="#fff" width="300">
                        <input type="text" readonly="readonly" value=\'[bemoove_tag="' . $val->name . '"]\'" />
                    </td>
                    </tr>
                    <tr>
                    <td valign="top" bgcolor="#ccc">ソース</td>
                    <td valign="top" bgcolor="#fff"><textarea rows="3" style="width:100%;">' . $this -> create_BeMoOve_src($val->name) . '</textarea></td>
                    </tr>
                    <tr>
                    <td valign="top" bgcolor="#ccc">ビデオサイズ</td>
                    <td valign="top" bgcolor="#fff"></td>
                    </tr>
                    <tr>
                    <td valign="top" bgcolor="#ccc">再生時間</td>
                    <td valign="top" bgcolor="#fff"></td>
                    </tr>

                    <tr>
                    <td valign="top" bgcolor="#ccc" colspan="2" align="right">
                    ※現在変換処理中です。もうしばらくお待ち下さい。
                    <input type="button" value="削除" onclick="var res = confirm(\'本当に削除してもよろしいですか？\');if( res == true ) {location.href = \'admin.php?page=BeMoOve_movies_list&m=delete&hash='.$val->video_hash.'\'}else{}" />
                    </td>
                    </tr>
                    </table><br />');
                }
            }
//動画情報表示ここまで----------
?>
            </div>
<?php
        }
    }

    function create_BeMoOve_src($tagname) {
        $jwplayer
            = "<script type=\"text/javascript\"src=\"https://". WP_BeMoOve_SUBDOMAIN. ".behls-lite.jp/js/jwplayer.js\"></script>
<script type=\"text/javascript\">jwplayer.key=\"GExaQ71lyaswjxyW6fBfmJnwYHwXQ9VI1SSpWNtsQo4=\";</script>";

        $opt = get_option('BeMoOve_admin_datas');
        $account_id = $opt['account_id'];

        global $wpdb;

        $table_name = $wpdb->prefix . 'movie_meta';


        $get_list=$wpdb->get_results(
            $wpdb->prepare("SELECT * FROM " . $table_name . " WHERE NAME=\"" . $tagname . "\"", 0)
        );

        $width  =$get_list[0]->video_width;
        $height =$get_list[0]->video_height;

        $bemoove_code
         = "<div id=\"". $tagname . "\">Loading the player...</div>
<script type=\"text/javascript\">
    var isAndroid = false;
    var isIOS = false;
    var ua = navigator.userAgent.toLowerCase();
    if (ua.match(/Android/i)) var isAndroid = true;
    if (ua.match(/iP(hone|ad|od)/i)) var isIOS = true;
    if (!isAndroid && !isIOS) {
        jwplayer($tagname).setup({
            file: \"https://".WP_BeMoOve_SUBDOMAIN.".behls-lite.jp/media/video/{$account_id}/{$tagname}.m3u8\",
            image: \"https://".WP_BeMoOve_SUBDOMAIN.".behls-lite.jp/media/thumbnail/{$account_id}/{$tagname}\",
            width: \"$width\",
            height: \"$height\"
        });
    } else {
        document.getElementById(\"". $tagname . "\").innerHTML
            = \"\"
            + \"<video id=myVideo\"
            + \" src='https://" . WP_BeMoOve_SUBDOMAIN . ".behls-lite.jp/media/video/{$account_id}/{$tagname}.m3u8' \"
            + \" poster='https://" . WP_BeMoOve_SUBDOMAIN . ".behls-lite.jp/media/thumbnail/{$account_id}/{$tagname}' \"
            + \" width='$width' height='$height' \"
            + \" controls>\"
            + \" </video>\";
    }
</script>";

        return htmlspecialchars($jwplayer . "\r" . $bemoove_code);
    }


    function cmt_activate($table_name) {
        $cmt_db_version = '1.00';
        $installed_ver = get_option( 'cmt_meta_version' );
        // テーブルのバージョンが違ったら作成
        if( $installed_ver != $cmt_db_version ) {
            $sql = "CREATE TABLE " . $table_name . " (
                movie_id int(11) NOT NULL AUTO_INCREMENT,
                post_id int(11) DEFAULT '0' NOT NULL,
                name varchar(100) NOT NULL,
                hash_name varchar(50) NOT NULL,
                video_hash varchar(50) NOT NULL,
                video_file varchar(255) NOT NULL,
                video_width int(5) NOT NULL,
                video_height int(5) NOT NULL,
                video_time varchar(20) NOT NULL,
                thumbnail_hash varchar(50) NOT NULL,
                thumbnail_file varchar(255) NOT NULL,
                views bigint(20) NOT NULL DEFAULT '0',
                create_date timestamp on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                callback_date timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
                redirectSuccess_code int(10) NOT NULL DEFAULT '0',
                callback_code int(10) NOT NULL DEFAULT '0',
                search_word text NOT NULL,
                search_tags text NOT NULL,
                free_movie int(1) NOT NULL DEFAULT '0',
                flag int(1) NOT NULL DEFAULT '1',
                UNIQUE KEY movie_id (movie_id)
            )
            CHARACTER SET 'utf8';";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            update_option('cmt_meta_version', $cmt_db_version);
        }
    }

    function save_meta($post_id) {
        if (!isset($_POST[$this->table_name])) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )  return;
        if ( !wp_verify_nonce( $_POST[$this->table_name], plugin_basename( __FILE__ ) ) )  return;

        global $wpdb;
        global $post;

        //リビジョンを残さない
        if ($post->ID != $post_id) return;

        $movie_post_id = isset($_POST['movie_post_id']) ? $_POST['movie_post_id'] : null;
        $free_movie = isset($_POST['free_movie']) ? $_POST['free_movie'] : null;

        //サーチテキスト用
        $status = 'publish';
        $type = 'post';
        $post_table=$wpdb->posts;

        $results = $wpdb->get_results($wpdb->prepare("
            SELECT *
            FROM ".$post_table."
            WHERE ".$post_table.".post_status = '%s' and ".$post_table.".post_type = '%s' and ".$post_table.".ID = '%d'
            ", $status , $type , $post_id));

        $search_txt=$results[0]->post_title.' ';
        $search_tags='##';
        $posttags = get_the_tags($results[0]->ID);
        if ($posttags) {
            foreach($posttags as $tag) {
                $search_txt.=''.$tag->name.' ';
                $search_tags.=''.$tag->name.'##';
            }
        }
        $search_txt.=$results[0]->post_content.' ';

        //保存するために配列にする
        $set_arr2 = array(
            'post_id' => $post_id,
            'free_movie' => $free_movie,
            'search_word' => $search_txt,
            'search_tags' => $search_tags
        );
        $wpdb->update( $this->table_name, $set_arr2, array('movie_id' => $movie_post_id));
        $wpdb->show_errors();
    }


    function get_pagination() {
        $m_hr=WP_BeMoOve_ITEMS_LIMIT;//表示最大数
        global $wpdb;

        if($_GET["s"] == ""){$_GET["s"] = 0;}
            $get_list = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM
                ".$this->table_name. "", 0)
            );
            $max=count($get_list);
            if($_GET['s'] == ""){
                $start = 0;
                $_GET['s'] = 0;
            }else{
                $start=$_GET['s'];
            }
        $page=$max/$m_hr;
        $end=$start+$m_hr;
        for($m = 0; $m < $page; $m++){
            $p=$m+1;
            $s=$m*$m_hr;
            if($s==$_GET[s]){
                $page_h.="
                <div class=page_on>".$p."</div>
                ";
            }else{
                $page_h.="
                <div class=page><a href=\"admin.php?page=BeMoOve_movies_list&s=".$s."\">".$p."</a></div>
                ";
            }
        }
        return $page_h;
    }



    function wp_custom_admin_css() {
        $url = WP_BeMoOve__PLUGIN_URL . 'css/style.css';
        echo '<!-- custom admin css -->
        <link rel = "stylesheet" href = "'.$url.'" type="text/css" media="all" />
        <!-- /end custom adming css -->';
    }


}
