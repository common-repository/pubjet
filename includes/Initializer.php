<?php

namespace triboon\pubjet\includes;

defined('ABSPATH') || exit;

class Initializer extends Singleton {

    /**
     * @return void
     */
    public function init() {
        Cron::getInstance();
        Backlink::getInstance();
        DBLoader::getInstance();
        RestApi::getInstance();
        Actions::getInstance();
        Filters::getInstance();
        ReportagePost::getInstance();
        RewriteRequest::getInstance();
        AssetsLoader::getInstance();
        Ajax::getInstance();
        Metaboxes::getInstance();
        RestApi::getInstance();
        Shortcodes::getInstance();
        \triboon\pubjet\includes\notices\Initializer::getInstance();
    }

}