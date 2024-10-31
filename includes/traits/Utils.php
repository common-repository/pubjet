<?php

namespace triboon\pubjet\includes\traits;

use triboon\pubjet\includes\enums\EnumAjaxPrivType;

defined('ABSPATH') || exit;

trait Utils {

    /**
     * @return void
     * @since  1.0
     * @author Triboon
     */
    public function ajax($name, $callback, $type = EnumAjaxPrivType::Both, $priority = 15) {
        if (EnumAjaxPrivType::LoggedIn === $type) {
            add_action('wp_ajax_pubjet-' . $name, $callback, $priority);
        } else if (EnumAjaxPrivType::Anonymous === $type) {
            add_action('wp_ajax_nopriv_pubjet-' . $name, $callback, $priority);
        } else if (EnumAjaxPrivType::Both === $type) {
            add_action('wp_ajax_pubjet-' . $name, $callback, $priority);
            add_action('wp_ajax_nopriv_pubjet-' . $name, $callback, $priority);
        }
    }

    /**
     * @return void
     * @since  1.0
     * @author Pish00k
     */
    public function checkNonce($nonce = false) {
        // Get from $_POST
        $nonce = $this->post('security');
        // Get from $_GET
        if (!$nonce) {
            $nonce = $this->get('security');
        }
        // Get from $_SERVER header
        if (!$nonce) {
            $nonce = pubjet_isset_value($_SERVER['HTTP_X_PUBJET_NONCE']);
        }
        if (!wp_verify_nonce($nonce, 'pubjet-nonce')) {
            $this->permissionError();
        }
    }

    /**
     * @return void
     * @since  1.0
     * @author Triboon
     */
    public function permissionError() {
        $this->error(pubjet__('permission-error'));
    }

    /**
     * @return void
     * @since  1.0
     * @author Triboon
     */
    public function error($message, $args = []) {
        pubjet_ajax_error($message, $args);
    }

    /**
     * @return void
     * @since  1.0
     * @author Triboon
     */
    public function success($data = []) {
        pubjet_ajax_success($data);
    }

    /**
     * @return void
     * @since  1.0
     * @author Triboon
     */
    public function checkAdminPermission() {
        if (!is_user_logged_in()) {
            $this->permissionError();
        }
        if (!pubjet_is_admin()) {
            $this->permissionError();
        }
    }

    /**
     * @return string|array
     * @since  1.0
     * @author Triboon
     */
    public function option($key, $default = '') {
        return pubjet_option($key, $default);
    }

    /**
     * @return boolean
     * @since  1.0
     * @author Triboon
     */
    public function doingAjax() {
        return (defined('DOING_AJAX') && DOING_AJAX);
    }

    /**
     * @param        $name
     * @param string $default
     *
     * @return mixed|string
     */
    public function get($name, $default = '') {
        return (isset($_GET[$name]) && !empty($_GET[$name])) ? sanitize_text_field($_GET[$name]) : $default;
    }

    /**
     * @param        $name
     * @param string $default
     *
     * @return mixed|string
     */
    public function post($name, $default = '') {
        return (isset($_POST[$name]) && !empty($_POST[$name])) ? sanitize_textarea_field($_POST[$name]) : $default;
    }

    /**
     * @return bool
     * @since  1.0
     * @author Triboon
     */
    public function formatBoolean($value) {
        if (is_bool($value)) {
            return $value;
        }
        if (in_array($value, ['off', 'no', 'false'])) {
            return false;
        }

        return true;
    }

    /**
     * @param $url
     * @param $method
     * @param $headers
     * @param $body
     *
     * @return \WP_Error|array
     */
    public function request($url, $method = 'GET', $headers = [], $body = []) {
        $args = [
            'method'  => $method,
            'headers' => $headers,
            'body'    => $body,
        ];

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response));

        return ['code' => $response_code, 'body' => $response_body];
    }

    /**
     * @param $value
     *
     * @return void
     */
    public function endpointHandler($endpoint, $callback, $priority = 15) {
        add_action('pubjet-api_' . $endpoint, $callback, $priority);
    }

}