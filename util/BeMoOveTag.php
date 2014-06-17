<?php
class BeMoOveTag {

    const WP_BeMoOve_TAG_ATTR_NAME = 'bemoove_tag';

    /** wp_movie_metaのレコードデータ */
    private $dbData;

    private $overrideWidth;

    private $overrideHeight;

    function getName() {

        return $this->dbData->name;
    }

    function  isFlagOn() {

        return $this->dbData->flag == '1';
    }

    function  getVideoHash() {

        return $this->dbData->video_hash;
    }

    function getThumbnailFile() {

        return $this->dbData->thumbnail_file;
    }

    /**
     * サムネイル表示用のファイルパスを取得する。
     * ※変更後のURLはユーザ（管理者）入力値であるため、XSSに注意しhtmlエスケープを行ったものを表示する。
     * @return string サムネイル表示用のファイルパス
     */
    function getDispThumbnailFile() {

        $result
             = $this->isThumbnailFileOverridden()
                ? htmlspecialchars($this->getOverrideThumbnailFile())
                : $this->getThumbnailFile();

        return $result;
    }

    function getOverrideThumbnailFile() {

        return $this->dbData->override_thumbnail_file;
    }

    function isThumbnailFileOverridden() {

        return !empty($this->dbData->override_thumbnail_file);
    }

    function getVideoWidth() {

        return $this->dbData->video_width;
    }

    function getVideoHeight() {

        return $this->dbData->video_height;
    }

    function  getVideoTime() {

        return $this->dbData->video_time;
    }

    function __construct($dbData){

        $this->dbData = $dbData;
    }

    /**
     * タグ名[bemoove_tag=hoge(width, height)]からインスタンスを生成する。
     * @param $tagStr hoge(width, height)の部分の文字列
     * @return BeMoOveTagインスタンス
     */
    static function createInstance($tagStr) {

        $tagName = $tagStr;
        $overrideWidth = 0;
        $overrideHeight = 0;
        // 表示用の幅と高さが指定されている場合は設定 tagname(width, height) の形式
        if (strpos($tagStr, '(')) {
            $tagInfo = preg_split("/[,(]+/", $tagStr);
            $tagName = $tagInfo[0];
            $overrideWidth = intval($tagInfo[1]);
            $overrideHeight = intval($tagInfo[2]);
        }

        // wp_movie_metaテーブルからレコードを取得
        global $wpdb;
        $table_name = $wpdb->prefix . 'movie_meta';
        $get_list = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM " . $table_name . " WHERE NAME = %s", $tagName)
        );

        $result = new BeMoOveTag($get_list[0]);
        $result->overrideWidth = $overrideWidth;
        $result->overrideHeight = $overrideHeight;
        return $result;
    }

    /**
     * 貼り付けコードを取得する
     *
     * @param $domainName ドメイン名
     * @param $accountId アカウントID
     * @param $isIncludePlayer jwplayer.jsを含めるか否か
     * @return 貼り付けコード
     */
    function getEmbedSrc($domainName, $accountId, $isIncludePlayer) {

        $result = "";
        if ($isIncludePlayer === true) {
            $result = "<script type=\"text/javascript\"src=\"https://". WP_BeMoOve_SUBDOMAIN. ".behls-lite.jp/js/jwplayer.js\"></script>
<script type=\"text/javascript\">jwplayer.key=\"GExaQ71lyaswjxyW6fBfmJnwYHwXQ9VI1SSpWNtsQo4=\";</script>\r";
        }

        $result .= $this->createTagCore($domainName, $accountId);
        return $result;
    }

    /**
     * Html表示コピーペースト用の貼り付けコードを取得する
     *
     * @param $domainName ドメイン名
     * @param unknown $accountId アカウントID
     * @return Html表示コピーペースト用の貼り付けコード
     */
    function  getSrcForCopyPaste($domainName, $accountId) {

        return htmlspecialchars($this->getEmbedSrc($domainName, $accountId, true));
    }

    private function createTagCore($domainName, $accountId) {

        $showWidth = (isset($this->overrideWidth) && 0 < $this->overrideWidth) ? $this->overrideWidth : $this->dbData->video_width;
        $showHeight = (isset($this->overrideHeight) && 0 < $this->overrideHeight) ? $this->overrideHeight : $this->dbData->video_height;
        $showThumbnailFile = $this->getDispThumbnailFile();

        return "<div id=\"{$this->getName()}\">Loading the player...</div>
<script type=\"text/javascript\">
    var isAndroid = false;
    var isIOS = false;
    var ua = navigator.userAgent.toLowerCase();
    if (ua.match(/Android/i)) var isAndroid = true;
    if (ua.match(/iP(hone|ad|od)/i)) var isIOS = true;
    if (!isAndroid && !isIOS) {
        jwplayer({$this->getName()}).setup({
            file: \"https://{$domainName}.behls-lite.jp/media/video/{$accountId}/{$this->getName()}.m3u8\",
            image: \"{$showThumbnailFile}\",
            width: \"{$showWidth}\",
            height: \"{$showHeight}\"
        });
    } else {
        document.getElementById(\"{$this->getName()}\").innerHTML
            = \"\"
            + \"<video id=myVideo\"
            + \" src='https://{$domainName}.behls-lite.jp/media/video/{$account_id}/{$this->getName()}.m3u8' \"
            + \" poster='{$showThumbnailFile}' \"
            + \" width='{$showWidth}' height='{$showHeight}' \"
            + \" controls>\"
            + \" </video>\";
        }
</script>";
    }

    /**
     * 動画一覧画面の一動画あたりの表示情報を作成する。
     *
     * @param $domainName ドメイン名
     * @param unknown $accountId アカウントID
     * @return 動画一覧画面の一動画あたりの表示情報
     */
    function createListItemInfo($domainName, $accountId) {
        return '<div class="movie_listitem_wrap">'
                    . '<table cellpadding="5" cellspacing="1" bgcolor="#bbbbbb">'
                    . '<tr>'
                    . '<td bgcolor="#fff" width="130" rowspan="7" style="text-align:center;">'
                    . ($this->isFlagOn()
                          ? ('<img src="'.WP_BeMoOve__PLUGIN_URL.'/images/noimage.jpg" width="120">')
                          : ('<a href="admin.php?page=BeMoOve_movies_list&m=details&hash=' . $this->getVideoHash() . '"">'
                             . '<img src="' . $this->getDispThumbnailFile() . '" width="120">'
                             . '</a>'))
                    . '</td>'
                    . '<td valign="top" bgcolor="#ccc" width="90">貼り付け用タグ</td>'
                    . '<td valign="top" bgcolor="#fff" width="300">'
                    . '<input type="text" readonly="readonly" value=\'[' . self::WP_BeMoOve_TAG_ATTR_NAME . '="' . $this->getName() . '"]\'" />'
                    . '</td>'
                    . '</tr>'
                    . '<tr>'
                    . '<td valign="top" bgcolor="#ccc">ソース</td>'
                    . '<td valign="top" bgcolor="#fff">'
                    . '<textarea rows="3" style="width:100%;">' . $this->getSrcForCopyPaste($domainName, $accountId) . '</textarea></td>'
                    . '</tr>'
                    . '<tr>'
                    . '<td valign="top" bgcolor="#ccc">ビデオサイズ</td>'
                    . '<td valign="top" bgcolor="#fff">' . ($this->isFlagOn() ? '' : $this->getVideoWidth() . 'x' . $this->getVideoHeight()) . '</td>'
                    . '</tr>'
                    . '<tr>'
                    . '<td valign="top" bgcolor="#ccc">再生時間</td>'
                    . '<td valign="top" bgcolor="#fff">' . ($this->isFlagOn() ? '' : $this->getVideoTime()) . '</td>'
                    . '</tr>'
                    . '<tr>'
                    . '<td valign="top" bgcolor="#ccc" colspan="2" align="right">'
                    . ($this->isFlagOn() ? '現在変換処理中です。もうしばらくお待ち下さい。' : '')
                    . '<input type="button" value="削除" onclick="var res = confirm(\'本当に削除してもよろしいですか？\');if( res == true ) {location.href = \'admin.php?page=BeMoOve_movies_list&m=delete&hash='.$this->getVideoHash().'\'}else{}" />'
                    . '</td>'
                    . '</tr>'
                    . '</table>'
                    . '<input type="hidden" value="' . ($this->isFlagOn() ? '1' : '0') . '" class="flag" />'
                    . '<input type="hidden" value="' . $this->getName() . '" class="tag_name" />'
                    . '</div><br />';
    }
}
?>