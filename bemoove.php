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

require_once(WP_BeMoOve__PLUGIN_DIR . 'bemoove-config.php');
require_once(WP_BeMoOve__PLUGIN_DIR . 'util/BeMoOveTag.php');
require_once(WP_BeMoOve__PLUGIN_DIR . 'util/UserAccountInfo.php');

if (is_admin()) {
    require_once(WP_BeMoOve__PLUGIN_DIR . 'class.bemoove-admin.php');
    new BeMoOve_Admin_Class;
}

add_filter('the_content', 'BeMoOve_embedcode');


function BeMoOve_embedcode($text) {

    $userAccountInfo = UserAccountInfo::getInstance();

    $jwplayer = "<script type=\"text/javascript\"src=\"https://" . $userAccountInfo->getBehlsHost() . "/js/jwplayer.js\"></script>
<script type=\"text/javascript\">jwplayer.key=\"GExaQ71lyaswjxyW6fBfmJnwYHwXQ9VI1SSpWNtsQo4=\";</script>";

    $responsive = "<script type='text/javascript'>
window.onload = function() {
    var movie_wraps = document.getElementsByClassName('movie_wrap');
    if (movie_wraps && 0 < movie_wraps.length) {
        var initWidth = document.documentElement.clientWidth;
        for (var i = 0; i < movie_wraps.length; i++) {
            var mw = movie_wraps[i];
            var m = mw.childNodes[0];
            var m_style_width = m.style.width;
            m_style_width.replace('px', '');
            var m_width = parseInt(m_style_width, 10) * 1.5;
            if (initWidth < m_width) initWidth = m_width;
            console.log(initWidth);
        }

        var resize = function() {

            var portraitWidth, landscapeWidth;

            if (Math.abs(window.orientation) === 0) {
                if (/Android/.test(window.navigator.userAgent)) {
                    if (!portraitWidth) portraitWidth = document.documentElement.clientWidth;
                } else {
                    portraitWidth = document.documentElement.clientWidth;
                }
                document.body.style.zoom = portraitWidth / initWidth;
            } else {
                if (/Android/.test(window.navigator.userAgent)){
                    if (!landscapeWidth) landscapeWidth = document.documentElement.clientWidth;
                } else {
                    landscapeWidth = document.documentElement.clientWidth;
                }
                document.body.style.zoom = landscapeWidth / initWidth;
            }
        };

        window.onresize = function() {
            resize();
        };
        resize();
    }
};
</script>";

    $movies = preg_replace_callback('/\[' . BeMoOveTag::WP_BeMoOve_TAG_ATTR_NAME . '=\".+\"\]/', BeMoOve_shortcode, $text);

    return $jwplayer . $responsive . $movies;
}

function BeMoOve_shortcode($matches) {

    preg_match('/\".+?\"/', $matches[0], $tag);

    $tagName = trim($tag[0], "\"");
    $bemooveTag = BeMoOveTag::createInstance($tagName);

    $userAccountInfo = UserAccountInfo::getInstance();

    $bemoove_code = $bemooveTag->getEmbedSrc($userAccountInfo, false);

    return '<div class="movie_wrap">' . $bemoove_code . "</div>";
}
