<?php

namespace triboon\pubjet\includes\notices;

// Exit if accessed directly
use triboon\pubjet\includes\Singleton;

defined('ABSPATH') || exit;

class Initializer extends Singleton {

    /**
     * constructor.
     */
    public function init() {
        /**
         * The pubjet_admin_notices_classes hook.
         *
         * @since 1.0.0
         */
        $notices = apply_filters('pubjet_admin_notices_classes', [
            EnterToken::class,
        ]);
        if (is_array($notices) && !empty($notices)) {
            foreach ($notices as $notice_class) {
                // Initiate class
                if (method_exists($notice_class, 'getInstance')) {
                    $notice_class::getInstance();
                } else {
                    new $notice_class();
                }
            }
        }
    }

}