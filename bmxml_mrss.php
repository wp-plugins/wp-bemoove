<?php
//error_reporting(E_ALL);

/* Added for v 1.4 

  ダイナミックMRSS用
*/


              
              define('MYDIR_WPs',dirname ( __FILE__ ));
              $wp_root_path=explode('wp-content/plugins/',MYDIR_WPs);
              define('ABSPATH2',$wp_root_path[0]);
              require_once( ABSPATH2 . '/wp-load.php' ); 
              define( 'WPINC', 'wp-includes' );
              require_once(ABSPATH.'wp-includes/l10n.php');
              require_once(ABSPATH.'wp-includes/pomo/translations.php');
              require_once(ABSPATH.'wp-includes/plugin.php');
              require_once(ABSPATH.'wp-includes/post.php');
              require_once(ABSPATH.'wp-includes/user.php');
              require_once(ABSPATH.'wp-includes/cache.php');
              require_once(ABSPATH.'wp-includes/general-template.php');
              
              global $wpdb;
              $table_name = $wpdb->prefix . 'bm_mrss';
              define("CUR_DIR_MRSS",realpath(dirname(__FILE__)));
              define("BM_CG_PLUGIN_PATH",realpath(dirname(__FILE__)));
              require_once(CUR_DIR_MRSS."/bm_cgvidposts.php");
           
              //$pid=$_GET['inf'];
              
              $table_name = $wpdb->prefix . 'bm_mrss';
              $get_list = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM $table_name", $pid)
              );   
              $num=count($get_list);
              $item = '';
              if($num>0){

                foreach ($get_list as $key => $value) {
                  foreach ($value as $key2 => $value2){
                    if($key2=='sitename'){ $sitename=$value2; }
                    if($key2=='siteurl'){ $siteurl=$value2; }
                    if($key2=='videotitle'){ $videotitle=$value2; }
                    if($key2=='videolink'){ $videolink=$value2; }
                    if($key2=='videothumbnail'){ $videothumbnail=$value2; }
                    if($key2=='videocategory'){ $videocategory=$value2; }
                    if($key2=='shortdesc'){ $shortdesc=$value2; 
                      $categories = json_encode($videocategory);
                $categories = explode('/', $categories);
                $dcatz = '';
                $p=0;
                foreach ($categories as $ckey => $cvalue) {
                  $p++;
                  if($cvalue!='"' && $p>1){ $dcatz .=', '; }
                  $remdel =  str_replace('"', "", $cvalue );
                  $dcatz .= str_replace('\\', "", $remdel );
                }

                      $item .= "<item> \n
                  <title>".$videotitle."</title> \n
                  <link>".$videolink."</link> \n
                  <description>".$shortdesc."</description> \n
                  <media:content url=\"".$videolink."\" fileSize=\"1000\" type=\"video\" expression=\"full\"> \n
                  <media:credit role=\"movie\">".$sitename."</media:credit> \n
                  <media:thumbnail url=\"".$videothumbnail."\" width=\"75\" height=\"50\" /> \n
                  <media:category>".$dcatz."</media:category> \n
                  <media:rating>nonadult</media:rating> \n
                  </media:content> \n
                </item>";

                    }
                  }
                } 
                
                $xml = "<rss version=\"2.0\" xmlns:media=\"http://search.yahoo.com/mrss/\"> \n
                <channel> \n ";
                $xml2 = "<title>".$sitename."</title> \n
                <link>".$siteurl."</link> \n";

                

            $xml3 = "</channel></rss>";

            header('Content-type: application/xml');  
            echo $xml . $xml2 . $item . $xml3; 
            }
          ?>