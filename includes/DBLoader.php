<?php

namespace triboon\pubjet\includes;

// Exit if accessed directly
defined('ABSPATH') || exit;

use triboon\pubjet\includes\db\Backlinks;
use triboon\pubjet\includes\db\Faileds;

class DBLoader extends Singleton {

    /**
     * @var Backlinks
     */
    public $backlinks;

    /**
     * constructor.
     */
    public function init() {
        $this->backlinks = new Backlinks();
    }

    /**
     * @return array
     */
    public function getTables() {
        /**
         * The pubjet_database_tables filter.
         *
         * @since 1.0.0
         */
        return apply_filters('pubjet_database_tables', [
            $this->backlinks,
        ]);
    }

}