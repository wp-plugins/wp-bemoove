<?php
/*
Plugin Name: WP_BeMoOve
Plugin URI: http://www.bemoove.jp/lp/wpplugin/
Description: Wordpressで動画を投稿、管理、再生するプラグイン
Author: ビムーブ株式会社 (BeMoOve Co.,Ltd)
Version: 1.3.2
Author URI: http://www.bemoove.jp/
*/

define('WP_BeMoOve_VERSION', '1.3.2');
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
} else {
    add_filter('wp_head', 'beMoOveHeader');
}

function beMoOveHeader() {
    $result = '';

    $css = "<style type='text/css'>
.copy_txt_wrap {
  position: relative;
  top: -35px;
  left: 50px;
  z-index: 9999;
  padding: 0.3em 0.5em;
  color: #FFFFFF;
  background: #c72439;
  border-radius: 0.5em;
}
</style>";
    $result .= $css;

    $userAccountInfo = UserAccountInfo::getInstance();

    $jwplayer = "<script type=\"text/javascript\"src=\"http://" . $userAccountInfo->getDeliveryBehlsHost() . "/js/jwplayer.js\"></script>
<script type=\"text/javascript\">jwplayer.key=\"/EsnRMoZKvjtE62Y1a6a0HYDZ5HxymN21cuVnA==\";</script>";
    $result .= $jwplayer;

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
            /* 右クリックでソースを表示する・現状コメントアウト
            mw.onmousedown = function(e) {
                if (e.which != 3) return;
                if (0 < this.getElementsByClassName('copy_txt_wrap').length) return;
                var copy_data = this.getElementsByClassName('copy')[0].value;
                var copyTxtWrap = document.createElement('div');
                copyTxtWrap.className = 'copy_txt_wrap';
                this.getElementsByClassName('copy_area')[0].appendChild(copyTxtWrap);
                var copyTxt = document.createElement('textarea');
                copyTxt.value = copy_data;
                copyTxtWrap.appendChild(copyTxt);
                copyTxt.onclick = function() { this.select(); };
                copyTxtWrap.onmouseout = function() {
                    copyTxt.onclick = null;
                    copyTxtWrap.onmouseout = null;
                    var copy_area = this.parentNode;
                    for (var i = 0; i < copy_area.childNodes.length; i++) {
                        copy_area.removeChild(copy_area.childNodes[i]);
                    }
                };
            };
            //*/
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
    $result .= $responsive;
    print($result);
}

add_filter('the_content', 'BeMoOve_embedcode');



function BeMoOve_embedcode($text) {

    $text = str_replace("!isAndroid &#038;&#038; !isIOS", "!isAndroid && !isIOS", $text);
    return preg_replace_callback('/\[' . BeMoOveTag::WP_BeMoOve_TAG_ATTR_NAME . '=(\"|&#8221;|&#8243;).+(\"|&#8221;|&#8243;)\]/', BeMoOve_shortcode, $text);
}

function BeMoOve_shortcode($matches) {

    preg_match('/(\"|&#8221;|&#8243;).+?(\"|&#8221;|&#8243;)/', $matches[0], $tag);

    $tagName = $tag[0];
    $tagName = str_replace(array("&#8221;", "&#8243;"), array("", ""), $tagName);
    $tagName = trim($tagName, "\"");

    $bemooveTag = BeMoOveTag::createInstance($tagName);

    $userAccountInfo = UserAccountInfo::getInstance();

    $bemooveCode = $bemooveTag->getEmbedSrc($userAccountInfo, false);
    $bemooveCodeForCopy = $bemooveTag->getEmbedSrc($userAccountInfo, true);

    return '<div class="movie_wrap">'
           . $bemooveCode
           . '<input type="hidden" class="copy" value="' . htmlspecialchars($bemooveCodeForCopy)  . '" />'
           . '<div class="copy_area"></div>'
           . "</div>";
}
