<?php

namespace triboon\pubjet\includes;

// Exit if accessed directly
defined('ABSPATH') || exit;

class SqlQueryBuilder {

    private $_select = [];
    private $_joins = [];
    private $_from = "";
    private $_limit = null;
    private $_start = 0;
    private $_fetch = 0;
    private $_sqlText = "";
    private $_mainTable = "";
    private $_where = null;
    private $_orderBy = null;
    private $_groupBy = null;
    private $_debug = false;

    private function regenerateQuery() {
        $this->_select    = [];
        $this->_joins     = [];
        $this->_from      = [];
        $this->_limit     = null;
        $this->_start     = 0;
        $this->_fetch     = 0;
        $this->_sqlText   = "";
        $this->_mainTable = "";
        $this->_where     = null;
        $this->_orderBy   = null;
        $this->_groupBy   = null;
    }

    public function limits($limits = null) {
        $this->_limit = $limits;

        return $this;
    }

    public function orderBy($orderBy = []) {
        $this->_orderBy = $orderBy;

        return $this;
    }

    public function groupBy($groupBy = []) {
        $this->_groupBy = $groupBy;

        return $this;
    }

    public function from($from) {
        $this->_mainTable = $from;

        return $this;
    }

    public function mainTable($mainTable) {
        $this->_mainTable = $mainTable;

        return $this;
    }

    public function where($where) {
        $this->_where = $where;

        return $this;
    }

    public function select($selectArr = []) {
        $this->regenerateQuery();
        $this->_select = $selectArr;

        return $this;
    }

    public function join($type = "", $table = "", $on = []) {
        array_push($this->_joins, ["type" => $type, "table" => $table, "on" => $on]);

        return $this;
    }

    public function order($order = []) {
        $this->order = $order;

        return $this;
    }

    /**
     * @param array  $param
     * @param string $logic
     */
    private function conditions($param = [], $logic = "and") {

        if (is_array($param)) {
            $isMultiDimensional = @is_array($param[0]);

            if ($isMultiDimensional) {
                foreach ($param as $item) {
                    if ($this->_debug) {
                        print_r($item);
                        exit();
                    }
                    $operator = "=";

                    if (isset($item["type"])) {
                        if ($item["type"] == "subset") {
                            if (isset($item["items"])) {
                                if (is_array($item["items"])) {
                                    $this->_sqlText .= " " . $logic . " ( 1<>1 ";

                                    foreach ($item["items"] as $subsetItem) {
                                        $this->conditions($subsetItem, "or");
                                    }

                                    $this->_sqlText .= ")";

                                    continue;
                                }
                            }
                        }
                    }


                    if (isset($item["operator"])) {
                        $operator = $item["operator"];
                    }

                    if ($operator == "in" || $operator == "is") {
                        $this->_sqlText .= " " . $logic . " " . $item["column"] . " " . $operator . " " . $item["value"] . " ";
                    } else {
                        $this->_sqlText .= " " . $logic . " " . $item["column"] . $operator . "'" . $item["value"] . "'";
                    }
                }

            } else {

                if (count($param)) {
                    $operator = "=";

                    if (isset($param["operator"])) {
                        $operator = $param["operator"];
                    }

                    if ($operator == "in" || $operator == "is") {
                        $this->_sqlText .= " " . $logic . " " . $param["column"] . " " . $operator . " " . $param["value"] . " ";
                    } else {
                        $this->_sqlText .= " " . $logic . " " . $param["column"] . $operator . "'" . $param["value"] . "'";
                    }
                }
            }
        }
    }

    /**
     * @return string
     */
    public function build() {

        $this->_sqlText = "";
        $this->_sqlText .= "select " . implode(", ", $this->_select);
        $this->_sqlText .= " from " . $this->_mainTable;

        //joins
        foreach ($this->_joins as $item) {
            $this->_sqlText .= " " . $item["type"] . " join " . $item["table"] . " on " . implode(" and ", $item["on"]);
        }

        //where block
        $this->_sqlText .= " where 1=1 ";

        $this->conditions($this->_where);

        //groupBy block
        if ($this->_groupBy) {
            $groupStr = " group by ";
            foreach ($this->_groupBy as $item) {
                $groupStr .= $item . ",";
            }
            $groupStr       = substr($groupStr, 0, strlen($groupStr) - 1);
            $this->_sqlText .= $groupStr;
        }

        //orderBy block
        if ($this->_orderBy) {
            if ( ! isset($this->_orderBy[0]) || ! is_array($this->_orderBy[0])) {
                $this->_orderBy = [$this->_orderBy];
            }
            $orderStr = " order by ";
            foreach ($this->_orderBy as $item) {
                $orderStr .= $item["field"] . " " . $item["dir"] . ",";
            }
            $orderStr       = substr($orderStr, 0, strlen($orderStr) - 1);
            $this->_sqlText .= $orderStr;
        }

        // limits
        if ($this->_limit) {
            $this->_sqlText .= " limit " . $this->_limit["start"] . "," . $this->_limit["limit"];
        }

        return $this->_sqlText;
    }
}