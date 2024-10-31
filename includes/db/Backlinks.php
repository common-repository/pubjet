<?php

namespace triboon\pubjet\includes\db;

defined('ABSPATH') || exit;

class Backlinks extends DbTable {

    /**
     * @access  public
     * @since   1.0
     */
    public function __construct() {
        global $wpdb;
        $this->table_name  = $wpdb->prefix . 'pubjet_backlinks';
        $this->primary_key = 'id';
        $this->version     = '1.0';
        $this->registerTable();
        parent::__construct();
    }

    /**
     * @access  public
     * @since   1.0
     */
    public function registerTable() {
        global $wpdb;
        $wpdb->pubjet_backlinks = $this->table_name;
    }

    /**
     * @access  public
     * @since   1.0
     */
    public function getColumns() {
        return [
            'id'          => '%d',
            'backlink_id' => '%d',
            'text'        => '%s',
            'url'         => '%s',
            'status'      => '%s',
            'position'    => '%s',
            'nofollow'    => '%d',
            'data'        => '%s',
            'created_at'  => '%s',
            'publish_at'  => '%s',
            'expired_at'  => '%s',
        ];
    }

    /**
     * @return array
     */
    public function findFutures() {
        global $wpdb;
        $query  = "SELECT * FROM {$this->table_name} WHERE `status` = 'future' AND `publish_at` <= %s";
        $pquery = $wpdb->prepare($query, pubjet_now_myql());
        pubjet_log($pquery);
        return $wpdb->get_results($pquery);
    }

    /**
     * @return array|boolean
     */
    public function findAll() {
        return $this->select(['*']);
    }

    /**
     * @return array
     */
    public function findActives($position = false) {
        global $wpdb;
        $query = "SELECT * FROM {$this->table_name} WHERE `status`=%s AND (`expired_at` IS NULL OR `expired_at` > %s)";
        if ($position && 'all' !== $position) {
            $query  .= ' AND `position`=%s';
            $pquery = $wpdb->prepare($query, 'publish', pubjet_now_myql(), $position);
        } else {
            $pquery = $wpdb->prepare($query, 'publish', pubjet_now_myql());
        }
        return $wpdb->get_results($pquery);
    }

    /**
     * @param $backlink_id
     *
     * @return array|boolean
     */
    public function findByBacklinkId($backlink_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE `backlink_id` = %d", $backlink_id));
    }

    /**
     * @param $backlink_id
     *
     * @return boolean
     */
    public function isBacklinkExists($backlink_id) {
        $backlink_row = $this->findByBacklinkId($backlink_id);
        return (bool)$backlink_row;
    }

    /**
     * @param $position
     *
     * @return array
     */
    public function findByPosition($position) {
        global $wpdb;
        $query = "SELECT * FROM {$this->table_name} WHERE `status` = %s AND `position`=%s AND (`expired_at` IS NULL OR `expired_at` > %s)";
        return $wpdb->get_results($wpdb->prepare($query, 'publish', $position, pubjet_now_myql()));
    }

    /**
     * @access  public
     * @since   1.0
     */
    public function createTable() {
        global $wpdb;
        $sql = "
			CREATE TABLE IF NOT EXISTS `{$this->table_name}`
			(
				`id`           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				`backlink_id`  VARCHAR(255) NOT NULL,
				`text`         VARCHAR(255) NOT NULL,
				`url` 		   TEXT NOT NULL,
				`position` 	   VARCHAR(32) NULL,
				`nofollow` 	   TINYINT(1) DEFAULT '0',
				`status` 	   VARCHAR(16) NULL,
				`data` 	       TEXT NULL,
				`created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
				`publish_at`   DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
				`expired_at`   DATETIME NULL,
				PRIMARY KEY (`id`)
			) {$wpdb->get_charset_collate()}
		";
        dbDelta($sql);
    }
}