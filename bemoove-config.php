<?php

/** BeHLsサーバーのホスト名 */  //Host name of the server BeHLs
define('BEHLS_HOST_NAME', 'wordpress.behls-lite.jp');

/** 動画配信サーバーのホスト名（CDNを利用する場合はここにCDNのホスト名を指定する） */ 
define('BEHLS_DELIVERY_HOST_NAME', 'wordpress.behls-lite.jp');

/** BeHLsサーバーとの通信プロトコル（http or https） */ 
//change protocol according to referer - リファラに応じてプロトコルを変更
$protocolbm = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || 
$_SERVER['SERVER_PORT'] == 443) ? $p="https" : $p="http";
define('PROTOCOL', $p);

/** アカウント操作を行う代理サーバーのホスト名 */ 
define('BEHLS_ADMIN_PROXY_HOST_NAME', 'dev.behls.jp/wp-bemoove');

/**
 * admin_apikey
 * アカウント操作権限をこのプラグインに委譲する場合は設定が必要    
 */
define('ADMIN_APIPREKEY', '');
?>