<?php
/* 
 V 1.4.0のために追加
 */
define("ENCRYPTION_KEY", "abcdefghijklmonpqrstuvwxyz1234567890");
global $wpdb;
class wp_cgmCLass{
   private static $_xmhost;
   private static $_xmuser;
   private static $_xmpass;
   private static $_xmdb;
   
  public function setxmValue($dn,$du,$dp,$dh){
    self::$_xmhost = $dh;
    self::$_xmuser = $du;
    self::$_xmpass = $dp;
    self::$_xmdb = $dn;
  }

  public function getxmValue(){
      $xmVal = array();
      $xmVal[]= self::$_xmhost;
      $xmVal[]= self::$_xmuser;
      $xmVal[]= self::$_xmpass;
      $xmVal[]= self::$_xmdb;
      return $xmVal;
  }

  public function getCon($f){
       
       $cgbm_fileMove=explode("wp-content\\plugins\\",$f);   ///<---  ただ機能はWP-サイトでdirを取得する
       if($cgbm_fileMove[0]==$f){ $cgbm_fileMove=explode("wp-content/plugins/",$f); }
       return $cgbm_fileMove[0];
   }
   
  
  public function bm_getposts(){  // <------------ この関数はbeMoOveビデオでの投稿を取得する
        
          $postsInfo=array();
            
          global $wpdb;
          $dv = 'publish';
          $v1="post";
          $v2=0;   
          $table_name = $wpdb->prefix . 'posts';
            $get_list = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM $table_name WHERE post_type = '%s' AND post_parent = '%d' AND post_status = '%s'  order by ID DESC", $v1, $v2, $dv)
            ); 
          foreach ($get_list as $key => $value) {
            $identifier=0;
            foreach ($value as $key2 => $value2){ 

              if($key2=='ID'){ 
                $id=$value2; 


              }
              if($key2=='post_date'){ $post_date=$value2; }
              if($key2=='post_content'){ 
                $post_content=$value2; 
                if (strstr($post_content, "://wordpress.behls-lite.jp/js/jwplayer.js")){ 
                  $tbnail = explode('image: "',$post_content);
                  $tbnail = explode('width:',$tbnail[1]); 
                  $t = explode('",',$tbnail[0]); 
                  $identifier=1;
                  }
                if(strstr($post_content, "[bemoove_tag=")){
                  $gInfo = explode('[bemoove_tag="',$post_content);
                  $gInfo = str_replace('["","','', $gInfo);
                  $gInfo = str_replace('\""]','', $gInfo);
                  $gInfo = str_replace('"]','', $gInfo[1]);
                  $gInfoFromScode=$this->IdentifyUsingScode($gInfo);
                  $isNull=count($gInfoFromScode[0]);
                  if($isNull!=0){                   
                    $x=0;
                    foreach($gInfoFromScode[0] as $key => $value) {

                      if($key=='video_file'){ $v=$value; }
                      if($key=='thumbnail_file'){ $t=$value; }

                      }
                    }
                    $identifier=1;
                  }
                }
              if($key2=='post_title'){ $post_title=$value2; }
              if($key2=='comment_count'){ 
                  if($identifier==1){
                    $postsInfo[] = array( 0 => $id, 1 => $post_date, 2 => $post_content, 3 => $post_title, 4=>$t ); 
                    }
                  }
          }
        }



        return $postsInfo;
  }

 public function checkAndDelIfDelPost(){  //<------------ additional
   
     global $wpdb;
          $v1="trash";
          $v2=0;   
          $table_name = $wpdb->prefix . 'posts';
          $table_name2 = $wpdb->prefix . 'bm_mrss';
            $get_list = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM $table_name", $v1)
            ); 
          foreach ($get_list as $key => $value) {
            $identifier=0;
            foreach ($value as $key2 => $value2){ 
              if($key2=='ID'){ $id = $key2; }
              if($key2=='post_status'){
                if($value2=='trash'){
                  $count = $wpdb->delete( $table_name2, array( 'postid' => "$id" ) );
                  
                }
              }
            }
          }
  }




  public function bm_getSiteInfo(){    // <---------------   関数は、サイト名とURLのようなサイトの情報を取得する

          global $wpdb;
            $v1='siteurl';
            $v2='blogname';
            $table_name = $wpdb->prefix . 'options';
            $get_list = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM $table_name WHERE option_name = '%s' OR option_name='%s'", $v1, $v2)
            );  
              foreach ($get_list[1] as $key => $value) {
                if($key=='option_value'){ $retInfo[1]=$value; }
              }
              foreach ($get_list[0] as $key => $value) {
                if($key=='option_value'){ $retInfo[2]=$value; }
              }
            return($retInfo);
  }

  public function createXmlmRss($info){
    $xmlFN=$info['video_title'];
    $xml = "<rss version=\"2.0\" xmlns:media=\"http://search.yahoo.com/mrss/\"> \n
    <channel> \n
      <title>".$info['site_name']."</title> \n
      <link>".$info['site_url']."</link> \n
      <description>Discussion on different songs</description> \n
      <item> \n
        <title>".$info['video_title']."h</title> \n
        <link>".$info['video_link']."</link> \n
        <media:content url=\"".$info['video_link']."\" fileSize=\"1000\" type=\"video\" expression=\"full\"> \n
          <media:credit role=\"musician\">member of band1</media:credit> \n
          <media:category>".$info['video_categories']."</media:category> \n
          <media:rating>nonadult</media:rating> \n
        </media:content>
      </item>
    </channel>
  </rss>";

      $path = BM_CG_WP_DIR.'mrss/'.$xmlFN.'.xml';
      $myfile = fopen($path, "w");
      file_put_contents($path, $xml);

  }


  public function createMrssTb(){    //<-------------- この関数はMRSSで選ばれた記事のためにテーブルを作成するには
          
          global $wpdb;
            $table_name = $wpdb->prefix . 'bm_mrss';
            $sql="CREATE TABLE IF NOT EXISTS $table_name (
              mrss_id INT(255) NOT NULL AUTO_INCREMENT,
                postid INT(255) NOT NULL,
                sitename VARCHAR(250)  COLLATE utf8_general_ci NOT NULL,
                siteurl VARCHAR(250)  COLLATE utf8_general_ci NOT NULL,
                videotitle VARCHAR(250)  COLLATE utf8_general_ci NOT NULL,
                videocategory VARCHAR(250)  COLLATE utf8_general_ci NOT NULL,
                videolink VARCHAR(250)  COLLATE utf8_general_ci NOT NULL,
                videothumbnail VARCHAR(250)  COLLATE utf8_general_ci NOT NULL,
                shortdesc TEXT COLLATE utf8_general_ci,
              PRIMARY KEY (mrss_id))";
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql ); 

            $myChecker = $wpdb->get_row("SELECT * FROM $table_name");
            
            if(!isset($myChecker->shortdesc)){
              $wpdb->query("ALTER TABLE $table_name ADD shortdesc  TEXT COLLATE utf8_general_ci");
            }

        }

  public function createInsertMrss($info){ // <---------------- この関数はMRSSを追加する記事を選択し挿入するには
            global $wpdb;
            $table_name = $wpdb->prefix . 'bm_mrss';
            if($this->checkMrssExist($info['post_id'])==0){
            $sql="INSERT INTO $table_name(postid, sitename, siteurl, videotitle, videocategory, videolink, videothumbnail)
                       VALUES('".$info['post_id']."','".$info['site_name']."','".$info['site_url']."','".$info['video_title']."','".$info['video_categories']."','".$info['video_link']."','".$info['video_thumbnail']."')";
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql ); 
          }
          else{
            if($this->checkSameMrss($info)==0){
               $sql="UPDATE $table_name SET 
                            sitename = '".$info['site_name']."', 
                            siteurl = '".$info['site_url']."', 
                            videotitle = '".$info['video_title']."', 
                            videocategory = '".$info['video_categories']."', 
                            videolink = '".$info['video_link']."', 
                            videothumbnail = '".$info['video_thumbnail']."'
                        WHERE postid = '".$info['post_id']."'";
              require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
              dbDelta( $sql ); 
            }
          }
  }


  public function checkMrssExist($id){    //<------------------- ポストが既に作成MRSSている場合、この関数はチェックする
        $count=0;
         global $wpdb;
         $table_name = $wpdb->prefix . 'bm_mrss';
         $get_list = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM " . $table_name . " WHERE postid = %d", $id)
        );
        $count = count($get_list); 
        return $count;
  }


   public function checkSameMrss($info){    
        $count=0;
         global $wpdb;
         $table_name = $wpdb->prefix . 'bm_mrss';
         $get_list = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM " . $table_name . " WHERE postid = %d AND videotitle = '%s' AND videocategory = '%s'", $info['post_id'], $info['video_title'], $info['video_categories'])
        );
        $count = count($get_list); 
        return $count;
  }

  public function bm_mrss_checkdesc($id){
          
         global $wpdb;
         $value_li = 'NULL';
         $table_name = $wpdb->prefix . 'bm_mrss';
         $get_list = $wpdb->get_results(
                $wpdb->prepare("SELECT shortdesc FROM " . $table_name . " WHERE postid = %d", $id)
        );
        foreach($get_list as $get_listKey => $get_listData){
          foreach ($get_listData as $Newkeyli => $Newvalueli) {
            $value_li = $Newvalueli;
          }
        }
        return $value_li;
  }

  public function bm_mrss_addSdesc($inf){
        global $wpdb;
        $table_name = $wpdb->prefix . 'bm_mrss';
        $sql="UPDATE $table_name SET 
                        shortdesc = '".$inf[1]."'
                        WHERE postid = '".$inf[0]."'";
              require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql ); 
  }

  public function createXml_phpFile(){  // <---------------------  MRSSレコードからxmlファイルを伝えることが可能なPHPファイルを作成するための機能（動的）
    $cont='<?php

              define("CUR_DIR_MRSS",realpath(dirname(__FILE__)));
              define("BM_CG_PLUGIN_PATH",realpath(dirname(__FILE__)));
              require_once(CUR_DIR_MRSS."/bm_cgvidposts.php");
              $bmGetdbI2=new wp_cgmCLass();                                //<----  declare new variable that points to the class
              $BMWP_DIR_2=$bmGetdbI2->getCon(CUR_DIR_MRSS); 
              $bm_DBI_complete2 = $bmGetdbI2->gdbSource($BMWP_DIR_2);        //<--------------execute the function to get database information of the site
              define("BM_MYWP_DBNAME2", preg_replace("/\s+/", "", $bm_DBI_complete2[0]));
              define("BM_MYWP_DBUSER2", preg_replace("/\s+/", "", $bm_DBI_complete2[1]));
              define("BM_MYWP_DBPASS2", preg_replace("/\s+/", "", $bm_DBI_complete2[2]));
              define("BM_MYWP_DBHOST2", preg_replace("/\s+/", "", $bm_DBI_complete2[3]));
              define("BM_MYWP_PREFIX2", preg_replace("/\s+/", "", $bm_DBI_complete2[4]));
              $num=0;
              $pid=$_GET[\'inf\'];
              $conn = mysql_connect(BM_MYWP_DBHOST2,BM_MYWP_DBUSER2,BM_MYWP_DBPASS2);
              if(!$conn){ die("Access Denied!"); }
              else{ 
                @mysql_select_db(BM_MYWP_DBNAME2,$conn)or die("Permission Denied!"); 
                $sql="SELECT * FROM bm_mrss WHERE postid = \'$pid\'";
                $res=mysql_query($sql, $conn)or die(\'Error 404\');
                $num=mysql_num_rows($res);
              }
              if($num>0){
                $data=mysql_fetch_array($res);
                $xml = "<rss version=\"2.0\" xmlns:media=\"http://search.yahoo.com/mrss/\"> \n
                <channel> \n
                <title>".$data[\'sitename\']."</title> \n
                <link>".$data[\'siteurl\']."</link> \n
                <description>Discussion Here</description> \n
                <item> \n
                  <title>".$data[\'videotitle\']."</title> \n
                  <link>".$data[\'videolink\']."</link> \n
                  <media:content url=\"".$data[\'videolink\']."\" fileSize=\"1000\" type=\"video\" expression=\"full\"> \n
                  <media:credit role=\"movie\">Some movie</media:credit> \n
                  <media:thumbnail url=\"".$data[\'videothumbnail\']."\" width=\"75\" height=\"50\" /> \n
                  <media:category>".$data[\'videocategory\']."</media:category> \n
                  <media:rating>nonadult</media:rating> \n
                  </media:content>
                </item>
              </channel>
            </rss>";
            header(\'Content-type: application/xml\');  
            echo $xml; 
            }
          ?>';
        
        $file=BM_CG_PLUGIN_PATH.'/bmxml_mrss.php'; 
        $dfxml = fopen("$file", "w") or die("Unable to open file!"); 
        fwrite($dfxml, $cont); 
        fclose($dfxml);
  }

  public function IdentifyUsingScode($sCkey){     //<-------------  関数は、ショートコードを使用してビデオポストのポストの詳細情報を取得する
        global $wpdb;
        $table_name = $wpdb->prefix . 'movie_meta';
        $get_list = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM " . $table_name . " WHERE NAME = %s", $sCkey)
        );
        return $get_list;
  }



  public function createBM_account_table($SiteInf){    //<--------------  この関数はbehlsでWPサイトのアカウントの表を作成するには
            global $wpdb;
            $table_name = $wpdb->prefix . 'settings_meta_bm';
            $sql="CREATE TABLE IF NOT EXISTS $table_name (
              setting_id INT(255) NOT NULL AUTO_INCREMENT,
                site_name VARCHAR(250) NOT NULL,
                site_url VARCHAR(250) NOT NULL,
                acctount_type VARCHAR(250) NOT NULL,
                account_status VARCHAR(250) NOT NULL,
              PRIMARY KEY (setting_id))";
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql ); 
            $flagInfo=$this->checkBehlsAccountInfo($SiteInf[1]);
            if($flagInfo==0){
              $this->InsAcctInfo_beMoove($SiteInf);
            }
        }
  public function checkBehlsAccountInfo($inf){  // <---------------- アカウント情報がまだ存在している場合、この関数はチェックする
            $flag=0;
            global $wpdb;
            $table_name = $wpdb->prefix . 'settings_meta_bm';
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            $row = $wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$table_name." WHERE site_url = '%s'", $inf) );
            $flag=count($row);
            return $flag;
  }
  public function InsAcctInfo_beMoove($inf){  //<------------------  この関数は、テーブルにアカウント情報を入力します
            global $wpdb; 
            $table_name = $wpdb->prefix . 'settings_meta_bm';
            $sql="INSERT INTO $table_name(site_name,site_url,acctount_type,account_status)VALUES('".$inf[2]."','".$inf[1]."','wordpress','Lite')";
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql ); 
  }


  public function get_wpbmSubDomain(){  //<------------------ HTTPホストを取得する機能
    $wpbm_domainName = PROTOCOL;//$_SERVER['HTTP_HOST'];
    return $wpbm_domainName;
  }
  public function get_wpbmSubDomain_set(){ //<------------- 関数は、アカウントの種類を取得する
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
  public function get_wpbmSite_info(){ //<-------------  ウェブサイトの情報を取得するために機能する
            $inf=1;
            $rVal=array();
            global $wpdb;
            $table_name = $wpdb->prefix . 'settings_meta_bm'; 
            require_once( BM_CG_WP_DIR2 . 'wp-admin/includes/upgrade.php' );
            $row = $wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$table_name." WHERE setting_id='%d'", $inf) );
            foreach($row as $key => $value) {
              $rVal[]=$value;
            }
            return $rVal;
  }
  public function get_wpbmSubDomain_set2(){ //<------------- アカウントの状態を取得する機能
            $inf=1;
            $rVal='';
            global $wpdb;
            $table_name = $wpdb->prefix . 'settings_meta_bm'; 
            require_once( BM_CG_WP_DIR2 . 'wp-admin/includes/upgrade.php' );
            $row = $wpdb->get_row( $wpdb->prepare("SELECT * FROM ".$table_name." WHERE setting_id='%d'", $inf) );
            foreach($row as $key => $value) {
              if($key=='account_status'){ $rVal = $value; }
            }
            return $rVal;
  }

 
}

