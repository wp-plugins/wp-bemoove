<?php
/*
Plugin Name: WP_BeMoOve
Plugin URI: http://www.bemoove.jp/lp/wpplugin/
Description: Wordpressで動画を投稿、管理、再生するプラグイン
Author: ビムーブ株式会社 (BeMoOve Co.,Ltd)
Version: 1.0.0
Author URI: http://www.bemoove.jp/
*/

define('WP_BeMoOve_VERSION', '1.0.0');
define('WP_BeMoOve__PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_BeMoOve__PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_BeMoOve_DELETE_LIMIT', 100000);
define('WP_BeMoOve_ITEMS_LIMIT', 5);
define('WP_BeMoOve_SUBDOMAIN', 'wordpress');

if (is_admin()) {
    require_once(WP_BeMoOve__PLUGIN_DIR . 'class.bemoove-admin.php');
    new BeMoOve_Admin_Class;
}

add_filter('the_content', 'BeMoOve_embedcode');


function BeMoOve_embedcode($text) {
    $ret = preg_replace_callback('/\[bemoove_tag=\".+\"\]/', BeMoOve_shortcode, $text);
    $jwplayer = "<script type=\"text/javascript\"src=\"https://"
              . WP_BeMoOve_SUBDOMAIN
              . ".behls-lite.jp/js/jwplayer.js\"></script>
                <script type=\"text/javascript\">jwplayer.key=\"GExaQ71lyaswjxyW6fBfmJnwYHwXQ9VI1SSpWNtsQo4=\";
                </script>";

    return $jwplayer . $ret;

}

function BeMoOve_shortcode($matches) {

    preg_match('/\".+?\"/', $matches[0], $tag);

    $bemoove_tag = trim($tag[0], "\"");

    $opt = get_option('BeMoOve_admin_datas');
    $account_id = $opt['account_id'];

    global $wpdb;

    $table_name = $wpdb->prefix . 'movie_meta';


    $get_list = $wpdb->get_results(
          $wpdb->prepare("SELECT * FROM " . $table_name . " WHERE NAME=\"" . $bemoove_tag . "\"", 0)
          );

    $width  =$get_list[0]->video_width;
    $height =$get_list[0]->video_height;

    $bemoove_code = "<div id=$tag[0]>Loading the player...</div>
<script type=\"text/javascript\">
    var isAndroid = false;
    var isIOS = false;
    var ua = navigator.userAgent.toLowerCase();
    if (ua.match(/Android/i)) var isAndroid = true;
    if (ua.match(/iP(hone|ad|od)/i)) var isIOS = true;
    if (!isAndroid && !isIOS) {
        jwplayer($tag[0]).setup({
            file: \"https://".WP_BeMoOve_SUBDOMAIN.".behls-lite.jp/media/video/{$account_id}/{$bemoove_tag}.m3u8\",
            image: \"https://".WP_BeMoOve_SUBDOMAIN.".behls-lite.jp/media/thumbnail/{$account_id}/{$bemoove_tag}\",
            width: \"$width\",
            height: \"$height\"
        });
    } else {
        document.getElementById(\"". $bemoove_tag . "\").innerHTML
            = \"\"
            + \"<video id=myVideo\"
            + \" src='https://" . WP_BeMoOve_SUBDOMAIN . ".behls-lite.jp/media/video/{$account_id}/{$bemoove_tag}.m3u8' \"
            + \" poster='https://" . WP_BeMoOve_SUBDOMAIN . ".behls-lite.jp/media/thumbnail/{$account_id}/{$bemoove_tag}' \"
            + \" width='$width' height='$height' \"
            + \" controls>\"
            + \" </video>\";
    }
</script>";

    return $bemoove_code;
}
