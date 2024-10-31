<?php

namespace triboon\pubjet\includes;

if (!defined("ABSPATH")) exit;

class RewriteRequest extends Singleton {

    /**
     * @var string
     */
    public static $prefix = 'pubjet-api';

    /**
     * @return void
     */
    public function init() {
        add_action('init', [$this, 'setRewriteRules'], 10, 0);
        add_filter('query_vars', [$this, 'setQueryVars'], 15);
        add_action('template_include', [$this, "setTemplateInclude"], 15);
    }

    /**
     * @return void
     */
    public function setRewriteRules() {
        add_rewrite_rule('^' . self::$prefix . '/([^/]*)/?([^/]*)/?', 'index.php?pubjet_rest_query=$matches[1]&pubjet_rest_param=$matches[2]', 'top');
        flush_rewrite_rules();
    }

    /**
     * @param $query_vars
     *
     * @return mixed
     */
    public function setQueryVars($query_vars) {
        $query_vars[] = 'pubjet_rest_query';
        $query_vars[] = 'pubjet_rest_param';
        return $query_vars;
    }

    /**
     * @param $template
     *
     * @return void
     */
    public function setTemplateInclude($template) {
        global $wp_query;

        if (
            isset($wp_query->query) &&
            isset($wp_query->query['pubjet_rest_query']) &&
            !empty($wp_query->query['pubjet_rest_query'])
        ) {

            send_origin_headers();

            header('Content-Type: text/html; charset=' . get_option('blog_charset'));
            header('X-Robots-Tag: noindex');

            send_nosniff_header();
            nocache_headers();

            $action = $wp_query->query['pubjet_rest_query'];

            if (!has_action(self::$prefix . '_' . $action)) {
                die('0');
            }
            do_action(self::$prefix . '_' . $action);

            exit;
        }

        return $template;
    }

}