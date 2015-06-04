<?php
/*
Plugin Name: WP_BeMoOve
Plugin URI: http://www.bemoove.jp/lp/wpplugin/
Description: Wordpressで動画を投稿、管理、再生するプラグイン
Author: ビムーブ株式会社 (BeMoOve Co.,Ltd)
Version: 1.4.3
Author URI: http://www.bemoove.jp/
*/

define('WP_BeMoOve_VERSION', '1.4.3');
define('WP_BeMoOve__PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_BeMoOve__PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BM_CG_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WP_BeMoOve_DELETE_LIMIT', 100000);
define('WP_BeMoOve_ITEMS_LIMIT', 5);

require_once(WP_BeMoOve__PLUGIN_DIR . 'bemoove-config.php');
require_once(WP_BeMoOve__PLUGIN_DIR . 'util/BeMoOveTag.php');
require_once(WP_BeMoOve__PLUGIN_DIR . 'util/UserAccountInfo.php');

if (is_admin()) {
    require_once(WP_BeMoOve__PLUGIN_DIR . 'class.bemoove-admin.php');
    new BeMoOve_Admin_Class;
    require_once(WP_BeMoOve__PLUGIN_DIR . 'bm_cgvidposts.php'); //<-----  我々は、V1.4.0用に作成したクラスをインポート
    $bmGetdbI=new wp_cgmCLass();  
    $cgbm_fileInfo = $bmGetdbI->getCon(WP_BeMoOve__PLUGIN_DIR);
    define('BM_CG_WP_DIR2', $cgbm_fileInfo);                    
    $BMWP_DIR_=$bmGetdbI->getCon(WP_BeMoOve__PLUGIN_DIR);       //<------------ V1.4.0用wpsiteパスを取得する関数を実行する
    $bm_gSite_inf = $bmGetdbI->bm_getSiteInfo();                //<-----------------  V1.4.0用WP-データベースからサイト名とサイトURLを取得
    $bmGetdbI->createBM_account_table($bm_gSite_inf);           //<-----------------  V1.4.0用bemoove設定用のテーブルを作成します。
    
} else {
    add_filter('wp_head', 'wpbm_beMoOveHeader');
}

function wpbm_beMoOveHeader() {
    $wpbm_result = '';
    $wpbm_css = "<style type='text/css'>
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
    $wpbm_result .= $wpbm_css;

    if( have_posts() ) { //現在のページがV1.4.0のためのビデオの記事を持っている場合識別するために
            $flag_post_bm_vid=0;
            while ( have_posts() ) {
            the_post();
            global $post;
            if(strstr($post->post_content, "http://wordpress.behls-lite.jp/js/jwplayer.js")){ $flag_post_bm_vid++; }
            if(strstr($post->post_content, "[bemoove_tag=")){  $flag_post_bm_vid++;  }
            }
        }
  
    
    $wpbm_result .= $newPHPfunction;

    $wpbm_userAccountInfo = UserAccountInfo::getInstance();

    // ブラウジングサーバプロトコルと同じプロトコルを作成し、ビデオの記事を持っている場合のみ、このリンクを追加 - 更新をV1.4.0用
    if($flag_post_bm_vid!=0):
        $protocolbm = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || 
        $_SERVER['SERVER_PORT'] == 443) ? $p="https://" : $p="http://";
    $jwplayer = "<?php if($flag_post_bm_vid!=0): ?><script type=\"text/javascript\"src=\"".$p.$wpbm_userAccountInfo->getDeliveryBehlsHost() . "/js/jwplayer.js\"></script>
<script type=\"text/javascript\">jwplayer.key=\"/EsnRMoZKvjtE62Y1a6a0HYDZ5HxymN21cuVnA==\";</script> <?php endif; ?>";
  
    $wpbm_result .= $jwplayer;
    endif;


    if($flag_post_bm_vid!=0):
    $wpbm_responsive = "<script type='text/javascript'>
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
            /* 右クリックでソースを表示する・現状コメントアウト Present condition commented that displays the source by right-clicking
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
</script> ";
    $wpbm_result .= $wpbm_responsive;

    endif;

    print($wpbm_result);
}

add_filter('the_content', 'wpbm_BeMoOve_embedcode');

function wpbm_BeMoOve_embedcode($text) {

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
