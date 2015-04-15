<?php
/**
 * [wp_movie_meta]テーブルの情報をやりとりするクラス     class to exchange the information of [wp_movie_meta] table
 */
class WPMovieMetaDataAdapter {

    private $table_name;

    private function getWbdb() {

        global $wpdb;
        return $wpdb;
    }

    function __construct(){

        $this->table_name = $this->getWbdb()->prefix . 'movie_meta';
    }

    function createTable() {
        $sql = "CREATE TABLE {$this->table_name} (
            movie_id int(11) NOT NULL AUTO_INCREMENT
            , name varchar(100) NOT NULL
            , hash_name varchar(50) NOT NULL
            , video_hash varchar(50) NOT NULL
            , video_file varchar(255) NOT NULL
            , video_width int(5) NOT NULL
            , video_height int(5) NOT NULL
            , video_time varchar(20) NOT NULL
            , thumbnail_hash varchar(50) NOT NULL
            , thumbnail_file varchar(255) NOT NULL
            , create_date timestamp on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            , callback_date timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
            , redirectSuccess_code int(10) NOT NULL DEFAULT '0'
            , override_thumbnail_file varchar(255) NOT NULL
            , flag int(1) NOT NULL DEFAULT '1'
            , UNIQUE KEY movie_id (movie_id)
        ) CHARACTER SET 'utf8';";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // カラム追加分    -   Column Additions
        $wpdb = $this->getWbdb();
        if (!$this->isColumnExists('social_share_flag')) {
            $sql = "ALTER TABLE {$this->table_name} ADD social_share_flag int(1) NOT NULL DEFAULT '1';";
            $wpdb->query($sql);
        }
        if (!$this->isColumnExists('logo_file')) {
            $sql = "ALTER TABLE {$this->table_name} ADD logo_file varchar(255) NOT NULL;";
            $wpdb->query($sql);
        }
        if (!$this->isColumnExists('logo_link')) {
            $sql = "ALTER TABLE {$this->table_name} ADD logo_link varchar(255) NOT NULL;";
            $wpdb->query($sql);
        }
    }

    private function isColumnExists($coloumnName) {

        $wpdb = $this->getWbdb();
        $sql = "DESCRIBE {$this->table_name} {$coloumnName};";
        $ret = $wpdb->query($sql);
        return !empty($ret);
    }

    function  getCount() {

        $wpdb = $this->getWbdb();
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name}", 0));
    }

    function getAllData() {

        $wpdb = $this->getWbdb();
        return $wpdb->get_results("SELECT name FROM {$this->table_name}");
    }

    function getDataByName($name) {

        $wpdb = $this->getWbdb();
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE name = %s", $name));
    }

    function getDataByVideoHash($videoHash) {

        $wpdb = $this->getWbdb();
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE video_hash = %s", $videoHash));
    }

    function getTopDataFromOffset($offset, $rowCount) {

        $wpdb = $this->getWbdb();
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM "
                . $this->table_name
                . " order by movie_id desc limit %d, %d", $offset, $rowCount
        ));
    }

    function insert($records) {

        $wpdb = $this->getWbdb();
        $wpdb->insert($this->table_name, $records);
        $wpdb->show_errors();
    }

    function update($values, $condition) {

        $wpdb = $this->getWbdb();
        $wpdb->update($this->table_name, $values, $condition);
        $wpdb->show_errors();
    }

    function deleteAll() {

        $wpdb = $this->getWbdb();
        $wpdb->query($wpdb->prepare("DELETE FROM $this->table_name", 0));
    }

    function deleteByName($name) {

        $wpdb = $this->getWbdb();
        $wpdb->query($wpdb->prepare("DELETE FROM $this->table_name WHERE name = %s", $name));
    }

    function deleteByNames($nameArray) {
        $count = count($nameArray);
        if (0 < $count) {
            $wpdb = $this->getWbdb();
            $nameArrayTxt = '';
            for ($i = 0; $i < $count; $i++) {
                if (0 < $i) $nameArrayTxt .= ',';
                $nameArrayTxt .= '"' . $nameArray[$i] . '"';
            }
            $sql = "DELETE FROM $this->table_name WHERE name IN ({$nameArrayTxt})";
            $wpdb->query($wpdb->prepare($sql, 0));
        }
    }

    function deleteByVideoHash($video_hash) {

        $wpdb = $this->getWbdb();
        $wpdb->query($wpdb->prepare("DELETE FROM $this->table_name WHERE video_hash = %s", $video_hash));
    }
}
?>
