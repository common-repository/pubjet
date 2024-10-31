<?php

namespace triboon\pubjet\includes\db;

defined('ABSPATH') || exit;

use triboon\pubjet\includes\SqlQueryBuilder;

class DbTable {

    /**
     * The name of our database table
     *
     * @access  public
     * @since   1.0
     */
    public $table_name;

    /**
     * The version of our database table
     *
     * @access  public
     * @since   1.0
     */
    public $version;

    /**
     * The name of the primary column
     *
     * @access  public
     * @since   1.0
     */
    public $primary_key;

    /**
     * Get things started
     *
     * @access  public
     * @since   1.0
     */
    public function __construct() {
    }

    /**
     * Whitelist of columns
     *
     * @access  public
     * @return  array
     * @since   1.0
     */
    public function getColumns() {
        return [];
    }

    /**
     * Default column values
     *
     * @access  public
     * @return  array
     * @since   1.0
     */
    public function getColumnDefaults() {
        return [];
    }

    /**
     * Retrieve a row by the primary key
     *
     * @access  public
     * @return  object
     * @since   1.0
     */
    public function get($row_id) {
        global $wpdb;

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE $this->primary_key = %s LIMIT 1;", $row_id));
    }

    /**
     * Retrieve a row by a specific column / value
     *
     * @access  public
     * @return  object
     * @since   1.0
     */
    public function getBy($column, $row_id) {
        global $wpdb;
        $column = esc_sql($column);

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE $column = %s LIMIT 1;", $row_id));
    }

    /**
     * Retrieve a specific column's value by the primary key
     *
     * @access  public
     * @return  string
     * @since   1.0
     */
    public function getColumn($column, $row_id) {
        global $wpdb;
        $column = esc_sql($column);

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->get_var($wpdb->prepare("SELECT $column FROM $this->table_name WHERE $this->primary_key = %s LIMIT 1;", $row_id));
    }

    /**
     * Retrieve a specific column's value by the the specified column / value
     *
     * @access  public
     * @return  string
     * @since   1.0
     */
    public function getColumnBy($column, $column_where, $column_value) {
        global $wpdb;
        $column_where = esc_sql($column_where);
        $column       = esc_sql($column);

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->get_var($wpdb->prepare("SELECT $column FROM $this->table_name WHERE $column_where = %s LIMIT 1;", $column_value));
    }

    /**
     * @param $column name
     *
     * @return array|null
     * @since  1.0
     * @access public
     *
     */
    public function getColumnValues($column) {
        global $wpdb;
        $column = esc_sql($column);

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->get_col("SELECT {$column} FROM {$this->table_name}");
    }

    /**
     * Get distinct values in column
     *
     * @param $column \bleezlabs\hoopi\includes\db\column name
     *
     * @return array|null
     * @since  1.0
     * @access public
     *
     */
    public function getColumnValuesBy($column, $column_where, $column_value) {
        global $wpdb;
        $column       = esc_sql($column);
        $column_where = esc_sql($column_where);
        $column_value = esc_sql($column_value);

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->get_col($wpdb->prepare("SELECT {$column} FROM {$this->table_name} WHERE {$column_where} = %s", $column_value));
    }

    /**
     * @param $column_names
     *
     * @return array|null|object
     */
    public function select($column_names) {
        global $wpdb;
        foreach ($column_names as &$item) {
            $item = $wpdb->_escape($item);
        }
        $columns_safe_string = implode(',', $column_names);
        $query               = "SELECT {$columns_safe_string} FROM {$this->table_name}";
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->get_results($query);
    }

    /**
     * Get custom column values with condition
     *
     * @param        $column_names
     * @param        $column_name
     * @param        $column_value
     * @param string $orderby
     * @param string $order
     *
     * @return array|null|object
     */
    public function selectWhere($column_names, $column_name, $column_value, $orderby = '', $order = 'DESC') {
        global $wpdb;

        foreach ($column_names as &$item) {
            $item = esc_sql($item);
        }
        $columns = implode(',', $column_names);

        if ($order) {
            $order = esc_sql($order);
        }
        if ($orderby) {
            $orderby = esc_sql($orderby);
        }

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $query = "SELECT {$columns} FROM {$this->table_name} WHERE {$column_name} = ";
        if (is_string($column_value)) {
            $query .= '%s';
        } else {
            $query .= '%d';
        }
        if ($orderby) {
            $query .= " ORDER BY {$orderby} {$order}";
        }

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->get_results($wpdb->prepare($query, $column_value));
    }

    public function selectMultiple($column_names, $where_col_name, $where_col_values, $operator = 'OR') {
        global $wpdb;
        if (!$where_col_values) {
            return [];
        }
        foreach ($column_names as &$item) {
            $item = esc_sql($item);
        }
        $columns = implode(',', $column_names);

        $query = "SELECT {$columns} FROM {$this->table_name} WHERE ";
        foreach ($where_col_values as $col_value) {
            $query .= "{$where_col_name} = ";

            if (is_string($col_value)) {
                $query .= '%s ';
            } else {
                $query .= '%d ';
            }

            $query .= $operator . ' ';
        }

        // delete last operator
        $query = substr($query, 0, strlen($query) - (strlen($operator) + 1));

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->get_results($wpdb->prepare($query, $where_col_values));
    }


    /**
     * Count rows with specific condition
     *
     * @param string  $column_name  column name
     * @param integer $column_value column value
     *
     * @return null|string
     * @since  1.0
     * @access public
     *
     */
    public function countWhere($column_name, $column_value) {
        global $wpdb;

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name} WHERE {$column_name} = %d", $column_value));
    }

    /**
     * Count total rows
     *
     * @return null|string
     */
    public function countAll() {
        global $wpdb;

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
    }

    /**
     * Get size of table in KB
     *
     * @return string|null
     * @since 1.8
     */
    public function size() {
        global $wpdb;
        $query  = "
	        SELECT ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024) AS table_size_kb
                FROM information_schema.TABLES 
                WHERE table_schema = %s AND table_name = %s;
	    ";
        $pquery = $wpdb->prepare($query, DB_NAME, $this->table_name);
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->get_var($pquery);
    }

    /**
     * Insert a new row
     *
     * @access  public
     * @return  int
     * @since   1.0
     */
    public function insert($data) {
        global $wpdb;

        // Set default values
        $data = wp_parse_args($data, $this->getColumnDefaults());


        // Initialise column format array
        $column_formats = $this->getColumns();

        // Force fields to lower case
        $data = array_change_key_case($data);


        // White list columns
        $data = array_intersect_key($data, $column_formats);

        // Reorder $column_formats to match the order of columns given in $data
        $data_keys      = array_keys($data);
        $column_formats = array_merge(array_flip($data_keys), $column_formats);

        $wpdb->insert($this->table_name, $data, $column_formats);

        return $wpdb->insert_id;
    }

    /**
     * Update a row
     *
     * @access  public
     * @return  bool
     * @since   1.0
     */
    public function update($row_id, $data = [], $where = '') {

        global $wpdb;

        // Row ID must be positive integer
        $row_id = absint($row_id);

        if (empty($row_id)) {
            return false;
        }

        if (empty($where)) {
            $where = $this->primary_key;
        }

        // Initialise column format array
        $column_formats = $this->getColumns();

        // Force fields to lower case
        $data = array_change_key_case($data);

        // White list columns
        $data = array_intersect_key($data, $column_formats);

        // Reorder $column_formats to match the order of columns given in $data
        $data_keys      = array_keys($data);
        $column_formats = array_merge(array_flip($data_keys), $column_formats);

        if (false === $wpdb->update($this->table_name, $data, [$where => $row_id], $column_formats)) {
            return false;
        }

        return true;
    }

    /**
     * Delete a row identified by the primary key
     *
     * @access  public
     * @return  bool
     * @since   1.0
     */
    public function delete($row_id = 0) {

        global $wpdb;

        // Row ID must be positive integer
        $row_id = absint($row_id);

        if (empty($row_id)) {
            return false;
        }

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        if (false === $wpdb->query($wpdb->prepare("DELETE FROM $this->table_name WHERE $this->primary_key = %d", $row_id))) {
            return false;
        }

        return true;
    }

    /**
     * Delete by specific column value
     *
     *
     * @param $column_name
     * @param $column_value
     *
     * @return bool
     * @since  1.0
     * @access public
     *
     */
    public function deleteBy($column_name, $column_value) {
        global $wpdb;

        $column_name  = esc_sql($column_name);
        $column_value = esc_sql($column_value);

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        if (false === $wpdb->query($wpdb->prepare("DELETE FROM $this->table_name WHERE {$column_name} = %d", $column_value))) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     * @since  1.0
     * @author ChapraDev
     */
    public function clear() {
        global $wpdb;

        return $wpdb->query("DELETE FROM {$this->table_name}");
    }

    /**
     * Check if specific value exists or not
     *
     * @param $col_name  string name
     * @param $col_value string value
     *
     * @since  1.0
     * @access public
     *
     */
    public function exists($col_name, $col_value) {
        global $wpdb;
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        if ($wpdb->get_var($wpdb->prepare("SELECT 1 FROM {$this->table_name} WHERE {$col_name} = %s", $col_value))) {
            return true;
        }

        return false;

    }

    /**
     * @param $args
     *
     * @return array
     */
    public function filter($args = []) {
        global $wpdb;

        $args = wp_parse_args($args, [
            'page'    => 1,
            /**
             * The hoopi_admin_table_page_size filter.
             *
             * @since 1.0.0
             */
            'perpage' => apply_filters('hoopi_admin_table_page_size', 20),
            'orderby' => 'id',
            'order'   => 'desc',
            'join'    => false,
            'select'  => ['*'],
            'groupby' => false,
            'where'   => false,
            'limits'  => true,
        ]);

        $query = new SqlQueryBuilder();

        // Join
        if ($args['join']) {
            $query->select($args['select'])->join($args['join']['type'], $args['join']['table'], $args['join']['condition'])->from($this->table_name);
        } else {
            $query->select($args['select'])->from($this->table_name);
        }

        // Group by
        if ($args['groupby']) {
            $query->groupBy($args['groupby']);
        }

        // Where
        if ($args['where'] && is_array($args['where'])) {
            $query->where($args['where']);
        }

        // Order by
        $query->orderBy([
                            "field" => $args['orderby'],
                            "dir"   => $args['order'],
                        ]);

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $total_rows = $wpdb->get_var("SELECT COUNT(*) FROM (" . $query->build() . ") as x");

        // Limits
        if ($args['limits']) {
            $query->limits([
                               'start' => (absint($args['page']) - 1) * absint($args['perpage']),
                               'limit' => absint($args['perpage']),
                           ]);
        }

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $result    = $wpdb->get_results($query->build());
        $formatted = array_map(function ($item) {
            return $this->formatRowItem($item);
        }, $result);

        return [
            'data'       => $formatted,
            'pagination' => [
                'page'        => absint($args['page']),
                'perPage'     => absint($args['perpage']),
                'totalRows'   => $total_rows,
                'totalPages'  => ceil($total_rows / absint($args['perpage'])),
                'currentPage' => absint($args['page']),
                'totalItems'  => absint($total_rows),
            ],
        ];
    }

    /**
     * @param $row_item |array
     */
    public function formatRowItem($row_item) {
        return $row_item;
    }

    /**
     * @param string $table The table name
     *
     * @return bool          If the table name exists
     * @since  1.0
     */
    public function table_exists($table) {
        global $wpdb;
        $table = sanitize_text_field($table);

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        return $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE '%s'", $table)) === $table;
    }

    /**
     * Check if the table was ever installed
     *
     * @return bool Returns if the customers table was installed and upgrade routine run
     * @since  1.0
     */
    public function installed() {
        return $this->table_exists($this->table_name);
    }

    /**
     * @return bool|int
     * @since  1.0
     * @access public
     */
    public function dropTable() {
        global $wpdb;

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
        return $wpdb->query("DROP TABLE IF EXISTS {$this->table_name}");
    }

    /**
     * @return string
     */
    protected function getDbVersionOption() {
        return $this->table_name . '_db_version';
    }
}