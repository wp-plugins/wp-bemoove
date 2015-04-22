<?php
/*  V 1.4.0のために追加 */

global $wpdb;
$bmcg_p_dir=dirname(__FILE__);
define('BM_CG_PLUGIN_PATH', $bmcg_p_dir);
$bmcg_class = new wp_cgmCLass();
$cgbm_fileInfo = $bmcg_class->getCon(BM_CG_PLUGIN_PATH);
define('BM_CG_WP_DIR', $cgbm_fileInfo);
$bmcg_class->createMrssTb();

$bmcg_class->checkAndDelIfDelPost();

$bmcg_postswp[]=$bmcg_class->bm_getposts();


$bmSinf = $bmcg_class->get_wpbmSite_info(); 
define('BM_MYWP_SITENAME',$bmSinf[1]);
define('BM_MYWP_SITEURL',$bmSinf[2]);

// ビデオpostsテーブルのCSS
echo "

<style typpe='text/css'>

.btn_cmrss {
  margin:20px;	
  background: #3498db;
  background-image: -webkit-linear-gradient(top, #3498db, #2980b9);
  background-image: -moz-linear-gradient(top, #3498db, #2980b9);
  background-image: -ms-linear-gradient(top, #3498db, #2980b9);
  background-image: -o-linear-gradient(top, #3498db, #2980b9);
  background-image: linear-gradient(to bottom, #3498db, #2980b9);
  -webkit-border-radius: 5;
  -moz-border-radius: 5;
  border-radius: 5px;
  font-family: Arial;
  color: #ffffff;
  font-size: 14px;
  padding: 7px 10px 7px 10px;
  text-decoration: none;
  width:200px;
}

.btn_cmrss:hover {
  background: #3cb0fd;
  background-image: -webkit-linear-gradient(top, #3cb0fd, #3498db);
  background-image: -moz-linear-gradient(top, #3cb0fd, #3498db);
  background-image: -ms-linear-gradient(top, #3cb0fd, #3498db);
  background-image: -o-linear-gradient(top, #3cb0fd, #3498db);
  background-image: linear-gradient(to bottom, #3cb0fd, #3498db);
  text-decoration: none;
  color: #ffffff;
}

.CSSTableGenerator {
	margin:0px;padding:0px;
	width:100%;
	border:1px solid #000000;
	
	-moz-border-radius-bottomleft:0px;
	-webkit-border-bottom-left-radius:0px;
	border-bottom-left-radius:0px;
	
	-moz-border-radius-bottomright:0px;
	-webkit-border-bottom-right-radius:0px;
	border-bottom-right-radius:0px;
	
	-moz-border-radius-topright:0px;
	-webkit-border-top-right-radius:0px;
	border-top-right-radius:0px;
	
	-moz-border-radius-topleft:0px;
	-webkit-border-top-left-radius:0px;
	border-top-left-radius:0px;
}.CSSTableGenerator table{
    border-collapse: collapse;
        border-spacing: 0;
	width:100%;
	height:100%;
	margin:0px;padding:0px;
}.CSSTableGenerator tr:last-child td:last-child {
	-moz-border-radius-bottomright:0px;
	-webkit-border-bottom-right-radius:0px;
	border-bottom-right-radius:0px;
}
.CSSTableGenerator table tr:first-child td:first-child {
	-moz-border-radius-topleft:0px;
	-webkit-border-top-left-radius:0px;
	border-top-left-radius:0px;
}
.CSSTableGenerator table tr:first-child td:last-child {
	-moz-border-radius-topright:0px;
	-webkit-border-top-right-radius:0px;
	border-top-right-radius:0px;
}.CSSTableGenerator tr:last-child td:first-child{
	-moz-border-radius-bottomleft:0px;
	-webkit-border-bottom-left-radius:0px;
	border-bottom-left-radius:0px;
}.CSSTableGenerator tr:hover td{
	
}
.CSSTableGenerator tr:nth-child(odd){ background-color:#e5e5e5; }
.CSSTableGenerator tr:nth-child(even)    { background-color:#ffffff; }.CSSTableGenerator td{
	vertical-align:middle;
	
	
	border:1px solid #000000;
	border-width:0px 1px 1px 0px;
	text-align:left;
	padding:7px;
	font-size:12px;
	font-family:Arial;
	font-weight:normal;
	color:#000000;
}.CSSTableGenerator tr:last-child td{
	border-width:0px 1px 0px 0px;
}.CSSTableGenerator tr td:last-child{
	border-width:0px 0px 1px 0px;
}.CSSTableGenerator tr:last-child td:last-child{
	border-width:0px 0px 0px 0px;
}
.CSSTableGenerator tr:first-child td{
		background:-o-linear-gradient(bottom, #333333 5%, #191919 100%);	background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #333333), color-stop(1, #191919) );
	background:-moz-linear-gradient( center top, #333333 5%, #191919 100% );
	filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=\"#333333\", endColorstr=\"#191919\");	background: -o-linear-gradient(top,#333333,191919);

	background-color:#333333;
	border:0px solid #000000;
	text-align:center;
	border-width:0px 0px 1px 1px;
	font-size:14px;
	font-family:Arial;
	font-weight:normal;
	color:#ffffff;
}
.CSSTableGenerator tr:first-child:hover td{
	background:-o-linear-gradient(bottom, #333333 5%, #191919 100%);	background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #333333), color-stop(1, #191919) );
	background:-moz-linear-gradient( center top, #333333 5%, #191919 100% );
	filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=\"#333333\", endColorstr=\"#191919\");	background: -o-linear-gradient(top,#333333,191919);

	background-color:#333333;
}
.CSSTableGenerator tr:first-child td:first-child{
	border-width:0px 0px 1px 0px;
}
.CSSTableGenerator tr:first-child td:last-child{
	border-width:0px 0px 1px 1px;
}
.dpager{
	height:15px; 
	width:15px; 
	padding:5px; 
	background: rgba(101, 131, 232, 0.6); 
	text-align:center;
	cursor:pointer;
	display:inline;
}
.dpager:hover{
	height:15px; 
	width:20px; 
	padding:5px; 
	background:  rgba(218, 204, 68, 0.6);
	text-align:center;
	cursor:pointer;
	display:inline;
	color:#000;
}
.dpager_selected{
	height:15px; 
	width:20px; 
	padding:5px; 
	background: rgba(101, 208, 238, 0.8);
	text-align:center;
	cursor:pointer;
	display:inline;
	color:#000;
}
.dpager_selected:hover{
	height:15px; 
	width:20px; 
	padding:5px; 
	background:  rgba(218, 204, 68, 0.6);
	text-align:center;
	cursor:pointer;
	display:inline;
	color:#000;
}
.textwrapper
{
    border:1px solid #999999;
    margin:5px 0;
    padding:3px;
}
</style>";

if(isset($_GET['dpage'])){ $dpage=$_GET['dpage']; }else{ $dpage=1; }


if(isset($_POST['save_sdesc'])){
	$ad_sdesc_in = array();
	$ad_sdesc_in[] = $_POST['bm_id'];
	$ad_sdesc_in[] = $_POST['mrss_sdesc'];
    $bmcg_class->bm_mrss_addSdesc($ad_sdesc_in);
}



if(isset($_GET['adesc'])){
echo "<div class='bmoverlay' style='z-index: 100000; position:fixed; top: 0px; left:0px; right:0px; bottom:0px; background:rgba(22, 18, 22, 0.9);'></div>";	
echo "<div style='z-index: 110000; padding: 10px; position: fixed; top:50px; bottom: 50px; left: 350px; right:350px; background-color: #FFF; height: 400px; border: 1px solid #000; border-radius:5px;' id='dbm_mrss'>";
echo "<div style='color:red; float:right; margin-right:10px; margin-top:5px;'><a style='text-decoration:none; color: #D70C38; font-weight: bold;' href='?page=BeMoOve_vpost&dpage=$dpage' title='CLOSE'>X</a></div>";

	$bm_sdesc_val2=$bmcg_class->bm_mrss_checkdesc($_GET['adesc']);
	echo '<form name="sdesc" action="?page=BeMoOve_vpost&dpage='.$dpage.'" method="POST">
	    <input type="hidden" name="bm_id" value="'.$_GET['adesc'].'">
		<p><label style="font-weight:700;">簡易詳細:<br>
		<div class="textwrapper">
		<textarea name="mrss_sdesc" style="cursor: pointer;margin: 0; padding: 0; border-width: 0; width: 100%;  max-width: 100%; max-height: 280px; height: 280px;">';
		if($bm_sdesc_val2!='NULL' && $bm_sdesc_val2!=''){ echo $bm_sdesc_val2; }
		echo '</textarea>
		</div>
		</label>
 		<div style="float:right;"><input type="submit" name="save_sdesc" class="button-primary" value="保存"></div>
		</p>
	</form>';  
	  
echo "</div>";
}

echo "<div class='wrap'>
        <h2>MRSSの設定</h2>";
//echo "<h3>サイト名: <label style='color:#339933; font-weight: normal;'>".BM_MYWP_SITENAME.'</label></h3>'; //現在のサイト名 を表示します
//echo "<h3>サイトのURL: <label style='color:#339933; font-weight: normal;'>".BM_MYWP_SITEURL.'</label></h3>'; //現在のサイトのURL を表示する
echo "<h3>MRSSリンク: <a style='color:#339933; font-weight: normal;' target='_blank' href='".BM_MYWP_SITEURL."/wp-content/plugins/wp-bemoove/bmxml_mrss.php'>".BM_MYWP_SITEURL."/wp-content/plugins/wp-bemoove/bmxml_mrss.php</a></h3>";
echo "<table  class='CSSTableGenerator' >";
echo "
        <td style=''>件名</td>
        <td>動画説明</td>
        <td>コンテンツ</td>
        <td>カテゴリー</td>
        <td>日付</td>
        <td>アクション</td>
      </tr>
    </div>
 	 ";

// ディスプレイビデオの記事情報
$itemCount = 0;
$limit = 10;
$items = count($bmcg_postswp[0]);
$dpages = $items/$limit;

$start=(($dpage-1)*$limit)+1;
$end=$dpage*$limit;
$newInfo=array();
foreach($bmcg_postswp as $bmcgpKey => $bmcgData){
	foreach ($bmcgData as $Newkey => $Newvalue) {
		$itemCount++;
		if($itemCount>=$start && $itemCount<=$end){
			
		echo "<tr>
			<td style='vertical-align:top; width: 150px; padding:5px;'>$Newvalue[3] <br>";
			echo "<a href='".get_permalink( $Newvalue[0] )."' target='_blank'>ページを見る</a><br>";
			echo "</td>";
			echo "<td style='vertical-align:top; width: 200px; padding:5px;'>";
			    $bm_sdesc_val=$bmcg_class->bm_mrss_checkdesc($Newvalue[0]);
			    if($bm_sdesc_val!='NULL' AND $bm_sdesc_val!=''){ echo $bm_sdesc_val; }
			echo "</td>";
			echo "<td style='vertical-align:top; width: 200px; padding:5px;'>$Newvalue[2]</td>
			<td style='vertical-align:top; width: 100px; padding:5px;'>"; 
		echo "<ul style='margin-top: 0px;'>";
			$category_detail=get_the_category($Newvalue[0]);
				$categories = '';
				foreach($category_detail as $cd){
				echo '<li style="margin: 1px;">'.$cd->cat_name.'</li>';
				$categories .= $cd->cat_name.'/';
			}
		echo "</ul>";
		$newInfo[$itemCount]['post_id'] = $Newvalue[0];
		$newInfo[$itemCount]['site_name']=BM_MYWP_SITENAME;
		$newInfo[$itemCount]['site_url']=BM_MYWP_SITEURL;
		$newInfo[$itemCount]['video_link']=get_permalink( $Newvalue[0] );
		$newInfo[$itemCount]['video_title']=$Newvalue[3];
		$newInfo[$itemCount]['video_categories']=$categories;
		$newInfo[$itemCount]['video_thumbnail']=$Newvalue[4];
		echo "</td>
			<td style='vertical-align:top; width: 100px; padding:5px;'>$Newvalue[1]</td>
			<td style='vertical-align:top; width: 150px; text-align:center; padding:5px;'><br>";
			if($bm_sdesc_val=='NULL' || $bm_sdesc_val==null){ $btn_val='簡易説明を設定';  } else{ $btn_val='簡易説明を編集'; }
			echo "<a title='MRSS用の簡易説明を設定する' class='button-primary' href='?page=BeMoOve_vpost&inf=".$itemCount."&vmrss=".$Newvalue[0]."&adesc=".$Newvalue[0]."&dpage=".$dpage."' style='width: 150px;'>".$btn_val."</a><br><br>";
			echo "<a class='button-primary' href='?page=BeMoOve_vpost&inf=".$itemCount."&vmrss=".$Newvalue[0]."&dpage=".$dpage."' style='width: 150px;'>MRSSを出力する </a><br><br>";
			echo "</td></tr>";
		}
}
}
echo "</table><br>";

/// ページャ
if($items%$limit==0){ $real_pages = $dpages; }else{ $real_pages = $dpages+1; }
for($p=1;$p<=$real_pages;$p++){
	if($dpage==$p){ $bmp_class='dpager_selected'; }else{ $bmp_class='dpager'; }
	echo "<a style='text-decoration:none; font-weight:bold;' href='?page=BeMoOve_vpost&dpage=".$p."'><div class='".$bmp_class."' style=''>".$p."</div></a>";
}

if(isset($_GET['inf'])){ // ビューMRSSは、クリックしたら、それが存在し、 MRSS情報を表示するには、新しいタブを開いていない場合、我々はMRSSテーブルに選択された映像情報を挿入する必要があります
	$inf=$_GET['inf'];
	$bmcg_class->createInsertMrss($newInfo[$inf]);
	if(!isset($_GET['adesc'])):
	$vp=$_GET['vmrss'];
	echo "<div class='bmoverlay' style='z-index: 10000; position:fixed; top: 0px; left:0px; right:0px; bottom:0px; background:rgba(22, 18, 22, 0.9);'></div>";	
	echo "<div style='z-index: 110000; padding: 10px; position: fixed; top:50px; bottom: 50px; left: 30px; right:30px; background-color: #FFF; height: 400px; border: 1px solid #000; border-radius:5px;' id='dbm_mrss'>";
	  echo "<div style='color:red; float:right; margin-right:10px; margin-top:5px;'><a style='text-decoration:none; color: #D70C38; font-weight: bold;' href='?page=BeMoOve_vpost&dpage=$dpage' title='CLOSE'>X</a></div>";
	  //echo "URL: <a target='_blank' href='".BM_MYWP_SITEURL."/wp-content/plugins/wp-bemoove/bmxml_mrss.php?inf=".$_GET['vmrss']."' style='cursor:pointer;'>".BM_MYWP_SITEURL."/wp-content/plugins/wp-bemoove/bmxml_mrss.php?inf=".$_GET['vmrss'].'</a><br><br>';
	  $bmcg_getMrss[]=$bmcg_class->bm_getposts();
	  foreach($bmcg_getMrss as $bmcgpKey_mrss => $bmcgData_mrss){
	     foreach ($bmcgData_mrss as $Newkey_mrss => $Newvalue_mrss) {
	     	if($Newvalue_mrss[0]==$_GET['vmrss']){
	     		$valCount=0;
	     		$category_detail2=get_the_category($Newvalue_mrss[0]);
				$categories2 = '';
				foreach($category_detail2 as $cd2){
					$valCount++;
					$categories2 .= $cd2->cat_name.'/';
				}
				if($valCount<2){ $categories2 = str_replace("/", "", $categories2 ); }
	     		echo "&lt;item&gt;<br>";
	     		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &lt;title&gt;".$Newvalue_mrss[3]."&lt;/title&gt;<br>";
	     		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &lt;link&gt;".get_permalink( $Newvalue_mrss[0] )."&lt;link&gt;<br>";
	     		echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &lt;media:content url="'.BM_MYWP_SITEURL."/wp-content/plugins/wp-bemoove/bmxml_mrss.php?inf=".$_GET['vmrss'].'" fileSize="1000" type="video" expression="full"><br>';
	     			echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &lt;media:credit role="movie">'.$Newvalue_mrss[3].'&lt;/media:credit><br>';
	     			echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &lt;media:thumbnail url="'.$Newvalue_mrss[4].'" width="75" height="50"/><br>';
	     			echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &lt;media:category>'.$categories2.'&lt;/media:category><br>';
	     			echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &lt;media:rating&gt;nonadult&lt;/media:rating><br>';
	     		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &lt;/media:content&gt;<br>";
	     		echo "&lt;/item&gt;<br>";
	     	}
	     }
	   }
echo "</div>";
    endif;

}

?>
<script type="text/javascript">

	var varpage = <?php echo $dpage; ?>;
	$('.bmoverlay').click(function(){
		window.location = '?page=BeMoOve_vpost&dpage='+varpage;
	});
</script>




