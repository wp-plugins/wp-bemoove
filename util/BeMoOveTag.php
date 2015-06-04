<?php
class BeMoOveTag {

    const WP_BeMoOve_TAG_ATTR_NAME = 'bemoove_tag';

    /** wp_movie_metaのレコードデータ */ 
    private $dbData;

    private $overrideWidth;

    private $overrideHeight;

    private function getId() {

        return $this->dbData->movie_id;
    }

    private function getTagId() {

        return $this->getName() . '_' . rand(0, 999);
    }

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
     * サムネイル表示用のファイルパスを取得する。  -  
     * ※変更後のURLはユーザ（管理者）入力値であるため、XSSに注意しhtmlエスケープを行ったものを表示する。 
     * @return string サムネイル表示用のファイルパス   
     */
    function getDispThumbnailFile(UserAccountInfo $userAccountInfo) {

        $result
             = $this->isThumbnailFileOverridden()
                ? htmlspecialchars($this->getOverrideThumbnailFile())
                : str_replace(
                      $userAccountInfo->getBehlsHost()
                      , $userAccountInfo->getDeliveryBehlsHost()
                      , $this->getThumbnailFile()
                  );

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

    function isSocialShare() {

        return $this->dbData->social_share_flag == 1;
    }

    function getLogoFile() {

        return htmlspecialchars($this->dbData->logo_file);
    }

    function getLogoLink() {

        return htmlspecialchars($this->dbData->logo_link);
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
     * @param $userAccountInfo アカウント情報  
     * @param $isIncludePlayer jwplayer.jsを含めるか否か  
     * @return 貼り付けコード  
     */
    function getEmbedSrc(UserAccountInfo $userAccountInfo, $isIncludePlayer) {

        $result = "";
        if ($isIncludePlayer === true) {
            $behlsHostName = $userAccountInfo->getDeliveryBehlsHost();
            $result = "<script type=\"text/javascript\" src=\"".PROTOCOL."://{$behlsHostName}/js/jwplayer.js\"></script>
<script type=\"text/javascript\">jwplayer.key=\"GExaQ71lyaswjxyW6fBfmJnwYHwXQ9VI1SSpWNtsQo4=\";</script>\r";
        }

        $result .= $this->createTagCore($userAccountInfo, !$isIncludePlayer);
        return $result;
    }

    /**
     * Html表示コピーペースト用の貼り付けコードを取得する    
     *
     * @param $userAccountInfo アカウント情報  
     * @return Html表示コピーペースト用の貼り付けコード   
     */
    function  getSrcForCopyPaste($userAccountInfo) {

        return htmlspecialchars($this->getEmbedSrc($userAccountInfo, true));
    }

    private function createTagCore(UserAccountInfo $userAccountInfo, $isIdRandom = true) {

        $tagId = $isIdRandom ? $this->getTagId() : $this->getName();
        $showWidth = (isset($this->overrideWidth) && 0 < $this->overrideWidth) ? $this->overrideWidth : $this->dbData->video_width;
        $showHeight = (isset($this->overrideHeight) && 0 < $this->overrideHeight) ? $this->overrideHeight : $this->dbData->video_height;
        $showThumbnailFile = $this->getDispThumbnailFile($userAccountInfo);
        $behlsHostName = $userAccountInfo->getDeliveryBehlsHost();
        $accountId = $userAccountInfo->getAccountId();
        $isSocial = $this->isSocialShare();
        $logoFile = $this->getLogoFile();
        $logoLink = $this->getLogoLink();
        $aspect = $this->get_aspect($showWidth,$showHeight);

        $ret = "<div id=\"{$tagId}\">Loading the player...</div>"
             . "<script type=\"text/javascript\">"
             . "jwplayer(\"{$tagId}\").setup({"
             . "file: \"".PROTOCOL."://{$behlsHostName}/media/video/{$accountId}/{$this->getName()}.m3u8\","
             . "image: \"{$showThumbnailFile}\","
             . "width: \"100%\","
             . "aspectratio: \"{$aspect}\","
             . "androidhls:true,"
             . ($isSocial ? "sharing: {}," : "")
             . ((empty($logoFile) || empty($logoLink)) ? "" : "logo: { file: '{$logoFile}', link: '{$logoLink}' }")
             . "});"
             . "</script>";
        return preg_replace('/^(\s)+\r\n/m', '',$ret);
    }

    /**
     * 動画一覧画面の一動画あたりの表示情報を作成する。 
     *
     * @param $userAccountInfo アカウント情報   
     * @return 動画一覧画面の一動画あたりの表示情報   
     */
    function createListItemInfo(UserAccountInfo $userAccountInfo) {

        return '<div class="movie_listitem_wrap">'
                    . '<table cellpadding="5" cellspacing="1" style="background-color: #bbbbbb;">'
                    . '<tr>'
                    . '<td rowspan="7" style="text-align: center; background-color: #fff; width: 130px;">'
                    . ($this->isFlagOn()
                          ? ('<img src="'.WP_BeMoOve__PLUGIN_URL.'/images/noimage.jpg" width="120">')
                          : ('<a class="movie_detail_lnk" href="admin.php?page=BeMoOve_movies_list&m=details&hash=' . $this->getVideoHash() . '"">'
                             . '<img src="' . $this->getDispThumbnailFile($userAccountInfo) . '" width="120"><br /><span> 詳細>></span>' 
                             . '</a>'))
                    . '</td>'
                    . '<td style="background-color: #ccc; width: 110px; vertical-align: top;">貼り付け用タグ</td>'
                    . '<td style="background-color: #fff; width: 300px; vertical-align: top;">'
                    . '<input type="text" class="copy" readonly="readonly" value=\'[' . self::WP_BeMoOve_TAG_ATTR_NAME . '="' . $this->getName() . '"]\'" />'
                    . '</td>'
                    . '</tr>'
                    . '<tr>'
                    . '<td style="background-color: #ccc; vertical-align: top;">ソース<br /><span style="font-size: 7px;">※ HTMLに直接貼り付ける場合のソースコード </span></td>' 
                    . '<td style="background-color: #fff; vertical-align: top;">'
                    . '<textarea rows="3" class="copy" style="width:100%;">' . $this->getSrcForCopyPaste($userAccountInfo) . '</textarea></td>'
                    . '</tr>'
                    . '<tr>' 
                    . '<td style="background-color: #ccc; vertical-align: top;">ビデオサイズ</td>' 
                    . '<td style="background-color: #fff; vertical-align: top;">' . ($this->isFlagOn() ? '' : $this->getVideoWidth() . 'x' . $this->getVideoHeight()) . '</td>'
                    . '</tr>'
                    . '<tr>'
                    . '<td style="background-color: #ccc; vertical-align: top;">再生時間</td>' 
                    . '<td style="background-color: #fff; vertical-align: top;">' . ($this->isFlagOn() ? '' : $this->getVideoTime()) . '</td>'
                    . '</tr>'
                    . '<tr>'
                    . '<td colspan="2" style="background-color: #ccc; vertical-align: top; text-align: right;">'
                    . ($this->isFlagOn() ? '<span class="text-accent">※ 現在変換処理中です。もうしばらくお待ち下さい。</span>' : '') 
                    . '<!--<input type="button" value="Post" onclick="var ret = confirm(\'Are you sure you want to post this video to your wp site ?\');if( ret == true ){ alert(\''.mysql_real_escape_string($this->getSrcForCopyPaste($userAccountInfo)).'\'); }else{}" />-->' 
                    . '<input type="button" value="削除" onclick="var res = confirm(\'本当に削除してもよろしいですか？\');if( res == true ) {location.href = \'admin.php?page=BeMoOve_movies_list&m=delete&hash='.$this->getVideoHash().'\'}else{}" />' 
                    . '</td>'
                    . '</tr>'
                    . '</table>'
                    . '<input type="hidden" value="' . ($this->isFlagOn() ? '1' : '0') . '" class="flag" />'
                    . '<input type="hidden" value="' . $this->getName() . '" class="tag_name" />'
                    . '</div><br />';
    }
	
	
	
	
/*
動画サイズを取得し、横幅640にした場合の縦幅、アスペクト比を返す
*/
   function get_aspect($origin_width,$origin_height) {
	
		
		$x = $origin_width/640;
		
		$y = round($origin_width/$origin_height,4);
		
		if($y < 1.2222){ $aspect = "1:1"; }
		elseif($y >= 1.2222 && $y < 1.2500){ $aspect = "11:9"; }
		elseif($y >= 1.2500 && $y < 1.3241){ $aspect = "5:4"; }
		elseif($y >= 1.3241 && $y < 1.3333){ $aspect = "192:145"; }
		elseif($y >= 1.3333 && $y < 1.4933){ $aspect = "4:3"; }
		elseif($y >= 1.4933 && $y < 1.5000){ $aspect = "128:75"; }
		elseif($y >= 1.5000 && $y < 1.6000){ $aspect = "3:2"; }
		elseif($y >= 1.6000 && $y < 1.6667){ $aspect = "16:10"; }
		elseif($y >= 1.6667 && $y < 1.7067){ $aspect = "15:9"; }
		elseif($y >= 1.7067 && $y < 1.7600){ $aspect = "112:75"; }
		elseif($y >= 1.7600 && $y < 1.7778){ $aspect = "16:9"; }
		elseif($y >= 1.7778 && $y < 1.8286){ $aspect = "16:9"; }
		elseif($y >= 1.8286 && $y < 1.8963){ $aspect = "64:35"; }
		elseif($y >= 1.8963 && $y < 2.1333){ $aspect = "256:135"; }
		elseif($y >= 2.1333){ $aspect = "32:15"; }
		
		return $aspect;
	
   }
	
}
?>
