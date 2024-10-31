<?php

namespace triboon\pubjet\includes;

// Exit if accessed directly
defined('ABSPATH') || exit;

class Singleton {

    /**
     * @var Singleton
     */
    private static $instances = [];

    /**
     * @return mixed|static
     */
    final public static function getInstance() {
        $class = get_called_class();
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new static();
            self::$instances[$class]->init();
        }
        return self::$instances[$class];
    }

    /**
     * @throws \Exception
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }

    /**
     * @since 1.0.0
     */
    private function __clone() {
    }

    /**
     * @since 1.0.0
     */
    public function init() {
    }
}