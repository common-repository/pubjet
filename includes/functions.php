<?php

use Sentry\State\Scope;
use triboon\pubjet\includes\DBLoader;
use triboon\pubjet\includes\enums\EnumActions;
use triboon\pubjet\includes\enums\EnumHttpMethods;
use triboon\pubjet\includes\enums\EnumOldOptions;
use triboon\pubjet\includes\enums\EnumOptions;
use triboon\pubjet\includes\enums\EnumPostMetakeys;

/**
 * @param $name
 * @param $args
 *
 * @since 1.0
 */
function pubjet_shortcode($name, $args) {
    $result = '[' . $name;
    foreach ($args as $key => $value) {
        $result .= " {$key}='" . $value . "' ";
    }
    $result .= ']';

    return $result;
}

/**
 * @return boolean
 * @author Triboon
 * @since  1.0
 */
function pubjet_is_prod_mode() {
    return !defined('WP_ENVIRONMENT') || "production" === WP_ENVIRONMENT;
}

/**
 * @return boolean
 * @author Triboon
 * @since  1.0
 */
function pubjet_is_dev_mode() {
    return defined('WP_ENVIRONMENT') && "development" === WP_ENVIRONMENT;
}

/**
 * @param $arr_or_string
 *
 * @return string
 * @since  1.0
 * @author Triboon
 */
function pubjet_flat_string($arr_or_string, $separator = ' ') {
    return is_array($arr_or_string) ? implode($separator, $arr_or_string) : $arr_or_string;
}

/**
 * @param       $mixed
 * @param false $default
 *
 * @return false|mixed
 */
function pubjet_isset_value(&$mixed, $default = false) {
    return (isset($mixed) && !empty($mixed)) ? $mixed : $default;
}

/**
 * @since 1.0
 */
function pubjet_option($name, $default = '') {
    $value = get_option($name, $default);

    return $value ? $value : $default;
}

/**
 * @return string
 * @throws Exception
 */
function pubjet_get_random_api_key($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Get JSON string
 *
 * @param string $string
 *
 * @return mixed|string
 * @since  1.0
 * @access public
 */
function pubjet_get_json($string) {
    if (!$string) {
        return '';
    }
    $string = str_replace("\n", "|NEWLINE|", $string);
    $string = str_replace('\\', '', $string);
    $string = str_replace("|NEWLINE|", "\r\n", $string);

    return json_decode($string, true);
}

/**
 * @param $data
 *
 * @return array
 * @since 1.0
 */
function pubjet_api_success($data = []) {
    return ['success' => true, 'payload' => $data,];
}

/**
 * @param string $error
 * @param array  $args
 *
 * @return array|bool[]|mixed[]|string[]
 * @since 3.3.4
 */
function pubjet_ajax_error($error = '', $status_code = 403) {
    if (is_wp_error($error)) {
        $error = $error->get_error_messages();
    } else {
        if (!is_array($error)) {
            $error = [$error];
        }
    }
    if (is_array($error) && count($error) == 1) {
        $error = reset($error);
    }
    wp_send_json(['success' => false, 'error' => $error,], $status_code);
}

/**
 * @param $data
 *
 * @return void
 * @since 3.3.4
 */
function pubjet_ajax_success($data = []) {
    wp_send_json(['success' => true, 'payload' => $data,], 200);
}

/**
 * @return array|null|object
 * @since  1.0
 * @access public
 *
 */
function pubjet_get_site_admins() {
    global $wpdb;
    $query = "
		SELECT 
			u.ID, u.user_login, u.user_nicename, u.user_email, um2.meta_value as `mobile`
		    	FROM {$wpdb->users} u
		    INNER JOIN {$wpdb->usermeta} m ON m.user_id = u.ID
		    LEFT OUTER JOIN {$wpdb->usermeta} um2 ON um2.user_id = u.ID AND um2.meta_key = 'mobile'
		    WHERE m.meta_key = 'wp_capabilities'
		    AND m.meta_value LIKE '%administrator%'
		    ORDER BY u.user_registered
	";

    return $wpdb->get_results($query);
}

/**
 * @return array
 * @since 1.0
 */
function pubjet_get_site_admins_user_ids() {
    $admins = chapar_get_site_admins();
    if (!$admins) {
        return false;
    }

    return array_map(function ($admin) {
        return $admin->ID;
    }, $admins);
}

/**
 * @return integer
 * @since  1.0
 * @author Triboon
 */
function pubjet_now_ts() {
    return current_time('timestamp');
}

/**
 * @return string
 */
function pubjet_now_myql() {
    return current_time('mysql');
}

/**
 * @param $list
 *
 * @return string|array
 * @since 1.0
 */
function pubjet_class_names($list, $return_as_array = false) {
    $result = [];
    foreach ($list as $key => $value) {
        if (is_int($key)) {
            $result[] = $value;
        } else if ($value) {
            $result[] = $key;
        }
    }
    if ($return_as_array) {
        return $result;
    }

    return implode(' ', $result);
}

/**
 * @param $a
 * @param $b
 *
 * @return array
 * @since 1.0
 */
function pubjet_parse_args(&$a, $b) {
    $a      = (array)$a;
    $b      = (array)$b;
    $result = $b;
    foreach ($a as $k => &$v) {
        if (is_array($v) && isset($result[$k])) {
            $result[$k] = pubjet_parse_args($v, $result[$k]);
        } else {
            $result[$k] = $v;
        }
    }

    return $result;
}


/**
 * Check if current request is an ajax or not
 *
 * @return bool
 * @since 1.0
 *
 */
function pubjet_is_ajax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * @param string $user_id
 *
 * @return bool
 */
function pubjet_is_admin($user_id = '') {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    return user_can($user_id, 'manage_options');
}

/**
 * Get user ip address
 *
 * @return mixed|string
 * @since 1.0
 */
function pubjet_get_ip_address() {
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    } else if (isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
        $ipaddress = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
    } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    } else if (isset($_SERVER['REMOTE_ADDR'])) {
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    } else {
        $ipaddress = 'UNKNOWN';
    }

    return apply_filters('pubjet_user_ip', $ipaddress);
}

/**
 * @return void
 * @author Triboon
 * @since  1.0
 */
function pubjet_wp_log($data) {
    if (is_array($data) || is_object($data)) {
        error_log(print_r($data, true));

        return;
    }
    error_log($data);
}

/**
 * Get woocommerce checkout page url
 *
 * @return string
 * @author Triboon
 * @since  1.0
 */
function pubjet_find_woo_checkout_page_url() {
    return get_permalink(wc_get_page_id('checkout'));
}

/**
 * Get woocommerce shop page url
 *
 * @return string
 * @author Triboon
 * @since  1.0
 */
function pubjet_find_woo_shop_page_url() {
    return get_permalink(wc_get_page_id('shop'));
}

/**
 * @param       $item
 * @param array $args
 */
function pubjet_echo_or_call($item, $args = []) {
    if (is_callable($item)) {
        call_user_func($item, $args);

        return;
    }
    if (isset($args['return']) && $args['return']) {
        return $item;
    }
    echo is_array($item) ? implode(' ', $item) : $item;
}

/**
 * Get specific user roles
 *
 * @param string $user_id
 *
 * @author Triboon
 * @since  1.0
 */
function pubjet_find_user_roles($user_id = '') {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    $user = get_user_by('id', $user_id);
    if (!$user) {
        return [];
    }

    return $user->roles;
}

/**
 * @param $attributes
 */
function pubjet_html_tag_atts($attributes, $echo = true) {
    if (!is_array($attributes)) {
        return '';
    }
    $result = '';
    foreach ($attributes as $key => $value) {
        if ($value) {
            if (true === $value) {
                $result .= sprintf(' %s ', $key);
            } else {
                if (!empty(trim($value))) {
                    $result .= sprintf(' %s = "%s" ', $key, $value);
                }
            }
        }
    }
    if ($echo) {
        echo $result;
    }

    return $result;
}

/**
 * Conditionally render markup
 *
 * @param         $condition
 * @param Closure $render
 *
 * @since 1.0
 */
function pubjet_condition_render($condition, $render) {
    if (is_callable($condition)) {
        $condition = call_user_func($condition);
    }
    if ($condition) {
        if (is_callable($render)) {
            ob_start();
            call_user_func($render);
            $output = ob_get_clean();
        } else {
            $output = $render;
        }
        echo $output;
    }
}

/**
 * Sanitize the input.
 *
 * @param mixed $input  The input.
 * @param bool  $typefy Whether to convert strings to the appropriate data type.
 *
 * @return mixed
 * @since  1.0
 *
 */
function pubjet_sanitize($input, $typefy = false) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[sanitize_text_field($key)] = pubjet_sanitize($value, $typefy);
        }

        return $input;
    }

    // These are safe types.
    if (is_bool($input) || is_int($input) || is_float($input)) {
        return $input;
    }

    // Now we will treat it as string.
    $input = sanitize_text_field($input);

    // avoid numeric or boolean values as strings.
    if ($typefy) {
        return pubjet_typefy($input);
    }

    return $input;
}

/**
 * Load template part
 *
 * @param string $name
 * @param bool   $extend
 * @param bool   $include
 * @param array  $data
 *
 * @return bool|mixed|string|void
 * @since 1.0
 */
function pubjet_template($name, $extend = false, $include = true, $data = []) {

    if ($extend) {
        $name .= '-' . $extend;
    }

    $template     = false;
    $template_dir = [
        PUBJET_TPLS_DIR,
    ];

    /**
     * The pubjet_templates_dir filter.
     *
     * @since 1.0.0
     */
    $template_dir = apply_filters('pubjet_templates_dir', $template_dir);

    foreach ($template_dir as $temp_path) {
        if (file_exists($temp_path . $name . '.php')) {
            $template = $temp_path . $name . '.php';
            break;
        }
    }

    /**
     * The pubjet_load_template filter.
     *
     * @since 1.0.0
     */
    $template = apply_filters('pubjet_load_template', $template, $name);

    if (!$template || !file_exists($template)) {
        _doing_it_wrong(__FUNCTION__, sprintf("<strong>%s</strong> does not exists in <code>%s</code>.", $name, $template), '1.4.0');

        return false;
    }

    if (!$include) {
        return $template;
    }

    extract($data, EXTR_SKIP);
    include $template;
}

/**
 * Convert the input into the proper data type
 *
 * @param mixed $input The input.
 *
 * @return mixed
 * @since  1.0
 *
 */
function pubjet_typefy($input) {
    if (is_numeric($input)) {
        return floatval($input);
    } else if (is_string($input) && preg_match('/^(?:true|false)$/i', $input)) {
        return 'true' === strtolower($input);
    }

    return $input;
}

/**
 * @return array
 * @author Triboon
 * @since  1.0
 */
function pubjet_get_page_templates() {
    return apply_filters('pubjet_page_templates', [
        'page-templates/thankyou.php' => 'بازخورد تریبون',
    ]);
}

/**
 * @param      $args
 * @param bool $echo
 *
 * @since 1.0
 */
function pubjet_swiper($args, $echo = true) {
    $defaults = [
        'loop'             => true,
        'spaceBetween'     => 0,
        'slidesPerView'    => 1,
        'lazy'             => true,
        'autoHeight'       => false,
        'show_timebar'     => false,
        'autoPlay'         => [
            'delay'                => 3000,
            'disableOnInteraction' => true,
        ],
        'navigation'       => [
            'display' => true,
            'nextEl'  => '.swiper-button-next',
            'prevEl'  => '.swiper-button-prev',
        ],
        'pagination'       => [
            'display'   => true,
            'clickable' => true,
            'classes'   => '',
            'el'        => '.swiper-pagination',
        ],
        'scrollbar'        => [
            'display' => true,
            'el'      => '.swiper-scrollbar',
        ],
        'breakpoints'      => [
            '0'    => [
                'slidesPerView' => 1,
            ],
            '576'  => [
                'slidesPerView' => 2,
            ],
            '768'  => [
                'slidesPerView' => 3,
            ],
            '992'  => [
                'slidesPerView' => 4,
            ],
            '1200' => [
                'slidesPerView' => 5,
            ],
            '1400' => [
                'slidesPerView' => 6,
            ],
        ],
        'thumbs'           => [
            'show' => false,
            'data' => [],
        ],
        'data'             => [],
        'callback'         => [],
        'container_after'  => function () {
        },
        'container_before' => function () {
        },
    ];
    $args     = pubjet_parse_args($args, $defaults);
    if (isset ($args['id']) && $args['id']) {
        $args['id'] = str_replace("-", "_", $args['id']);
    } else {
        $args['id'] = 'carousel_' . uniqid(rand());
    }
    // Check if data is an array and is not empty
    if (!isset($args['data']) || empty($args['data']) || !is_array($args['data'])) {
        return;
    }
    // We need callbcak
    if (!isset($args['callback']) || empty($args['callback'])) {
        return;
    }
    ?>
    <div class="triboon-swiper-container">
        <?php
        if (isset($args['container_before']) && is_callable($args['container_before'])) {
            call_user_func($args['container_before']);
        }
        ?>
        <?php if (isset($args['thumbs']['show']) && $args['thumbs']['show']) { ?>
            <div class="thumbs thumbs-<?php echo esc_attr($args['id']) ?>">
                <div class="swiper-wrapper">
                    <?php
                    if (isset($args['thumbs']['data']) && $args['thumbs']['data'] && is_array($args['thumbs']['data'])) {
                        foreach ($args['thumbs']['data'] as $item) {
                            ?>
                            <div class="swiper-slide">
                                <span class="thumbs__title"><?php echo $item; ?></span>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>
        <?php } ?>
        <div id="<?php echo esc_attr($args['id']); ?>" class="swiper slides">
            <div class="swiper-wrapper">
                <?php foreach ($args['data'] as $index => $item) { ?>
                    <div class="swiper-slide">
                        <?php call_user_func($args['callback'], $item, $index); ?>
                    </div>
                <?php } ?>
            </div>
            <?php if (is_array($args['pagination']) && isset($args['pagination']['display']) && $args['pagination']['display']) { ?>
                <div class="swiper-pagination <?php echo esc_attr($args['pagination']['classes']); ?>"></div>
            <?php } ?>
            <?php if (is_array($args['scrollbar']) && $args['scrollbar'] && isset($args['scrollbar']['display']) && $args['scrollbar']['display']) { ?>
                <div class="swiper-scrollbar"></div>
            <?php } ?>
            <?php if (is_array($args['navigation']) && $args['navigation'] && isset($args['navigation']['display']) && $args['navigation']['display']) { ?>
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            <?php } ?>
        </div>
        <?php
        if (is_array($args['container_after']) && isset($args['container_after']) && is_callable($args['container_after'])) {
            call_user_func($args['container_after']);
        }
        ?>
    </div>
    <script type="text/javascript">
        if (null === window.pubjet_swipers || undefined === window.pubjet_swipers) {
            window.pubjet_swipers = [];
        }
        window.pubjet_swipers.push(<?php echo json_encode($args); ?>);
    </script>
    <?php
}

/**
 * Wrap element with a tag
 *
 * @param $args
 *
 * @return string
 * @since 1.0
 */
function pubjet_wrap_a($args) {
    $args = wp_parse_args($args, [
        'href'    => '',
        'target'  => '_self',
        'classes' => '',
        'render'  => function () {
        },
        'elem'    => false,
    ]);

    ob_start();

    if (trim($args['href'])) {
        echo sprintf('<a href="%s" target="%s" class="%s">', $args['href'], $args['target'], $args['classes']);
    } else {
        if ($args['elem']) {
            echo sprintf('<%s class="%s">', $args['elem'], $args['classes']);
        }
    }

    call_user_func($args['render']);

    if (trim($args['href'])) {
        echo '</a>';
    } else {
        echo '</' . $args['elem'] . '>';
    }

    return ob_get_clean();
}

/**
 * @param $entry
 * @param $method
 * @param $line
 *
 * @return false|int|void
 */
function pubjet_log($entry, $method = __METHOD__, $line = __LINE__) {

    if (!pubjet_is_debug_mode()) {
        return;
    }

    if (is_array($entry) || is_object($entry)) {
        $entry = print_r($entry, true);
    }

    $file  = pubjet_debug_dir();
    $file  = fopen($file, 'a');
    $bytes = fwrite($file, $method . "::" . current_time('mysql') . ":: line " . $line . "::" . $entry . "\n");
    fclose($file);

    return $bytes;
}

/**
 * @return bool
 */
function pubjet_is_debug_mode() {
    global $pubjet_settings;
    return boolval($pubjet_settings[\triboon\pubjet\includes\enums\EnumOptions::DebugMode]);
}

/**
 * @return array
 */
function pubjet_options() {
    return pubjet_settings();
}

/**
 * @return string
 */
function pubjet_debug_dir() {
    /**
     * The pubjet_debug_dir filter.
     *
     * @since 1.0.0
     */
    return apply_filters('pubjet_debug_dir', PUBJET_DIR_PATH . 'debug.txt');
}

/**
 * @return string
 */
function pubjet_token() {
    global $pubjet_settings;
    /**
     * The pubjet_token filter.
     *
     * @since 1.0.0
     */
    return apply_filters('pubjet_token', trim(pubjet_isset_value($pubjet_settings['token'], '')));
}

/**
 * @return void
 */
function pubjet_api_root() {
    /**
     * The pubjet_api_root filter.
     *
     * @since 1.0.0
     */
    return apply_filters('pubjet_api_root', PUBJET_API_ROOT);
}

/**
 * @param $post_id
 *
 * @return integer
 */
function pubjet_find_reportage_id($post_id) {
    return get_post_meta($post_id, EnumPostMetakeys::ReportageId, true);
}

/**
 * @return string
 */
function pubjet_post_type() {
    /**
     * The pubjet_post_type filter.
     *
     * @since 1.0.0
     */
    return apply_filters('pubjet_post_type', PUBJET_POST_TYPE);
}

/**
 * @param $post_id
 *
 * @return bool
 */
function pubjet_is_reportage($post_id) {
    $reportage_post_id = get_post_meta($post_id, \triboon\pubjet\includes\enums\EnumPostMetakeys::ReportageId, true);
    return !empty($reportage_post_id);
}

/**
 * @param $reportage_id
 *
 * @return void|bool|integer
 */
function pubjet_find_post_id_by_reportage_id($reportage_id) {
    global $wpdb;
    $sql  = "SELECT `post_id` FROM  {$wpdb->postmeta} WHERE `meta_key` = %s AND `meta_value` = %s LIMIT 1";
    $psql = $wpdb->prepare($sql, \triboon\pubjet\includes\enums\EnumPostMetakeys::ReportageId, $reportage_id);
    return $wpdb->get_var($psql);
}

/**
 * @param $reportage_id
 *
 * @return array|bool|int|object|stdClass|void
 */
function pubjet_find_backlink_by_id($backlink_id) {
    global $wpdb;
    $sql  = "SELECT * FROM  {$wpdb->pubjet_backlinks} WHERE `backlink_id` = %s LIMIT 1";
    $psql = $wpdb->prepare($sql, $backlink_id);
    return $wpdb->get_row($psql);
}

/**
 * @return array
 */
function pubjet_plugin_status() {
    /**
     * The pubjet_plugin_status filter.
     *
     * @since 1.0.0
     */
    return apply_filters('pubjet_plugin_status', [
        'title'         => get_bloginfo('name'),
        'description'   => get_bloginfo('description'),
        'wpVersion'     => get_bloginfo('version'),
        'phpVersion'    => phpversion(),
        'pubjetVersion' => PUBJ()->getVersion(),
        'restUrl'       => trailingslashit(site_url()) . rest_get_url_prefix(),
    ]);
}

/**
 * @since 1.0.0
 */
function pubjet_strings() {
    /**
     * The pubjet_strings hook.
     *
     * @since 1.0.0
     */
    return apply_filters('pubjet_strings', [
        'every-minute'               => esc_html__('Every Minute', 'pubjet'),
        'backlink-not-found'         => esc_html__('Backlink not found', 'pubjet'),
        'horizontal'                 => esc_html__('Horizontal', 'pubjet'),
        'vertical'                   => esc_html__('Vertical', 'pubjet'),
        'pbqs'                       => esc_html__('Process Data By Query String', 'pubjet'),
        'pbqs-hints'                 => esc_html__('By default, Pabjet uses the REST method to process information. If for any reason this method does not work and you want to use the old method of data processing, enable this option', 'pubjet'),
        'style'                      => esc_html__('Style', 'pubjet'),
        'backlinks'                  => esc_html__('Backlinks', 'pubjet'),
        'all-backlinks'              => esc_html__('All Backlinks', 'pubjet'),
        'footer_inner'               => esc_html__('Footer Inner', 'pubjet'),
        'footer_main'                => esc_html__('Footer Main', 'pubjet'),
        'footer_all'                 => esc_html__('Footer All', 'pubjet'),
        'sidebar_inner'              => esc_html__('Sidebar Inner', 'pubjet'),
        'sidebar_main'               => esc_html__('Sidebar Main', 'pubjet'),
        'sidebar_all'                => esc_html__('Sidebar All', 'pubjet'),
        'header_inner'               => esc_html__('Header Inner', 'pubjet'),
        'header_main'                => esc_html__('Header Main', 'pubjet'),
        'header_all'                 => esc_html__('Header All', 'pubjet'),
        'widget-title'               => esc_html__('Widget Title', 'pubjet'),
        'backlinks-position'         => esc_html__('Backlinks Position', 'pubjet'),
        'pubjet-backlinks'           => esc_html__('Pubjet Backlinks', 'pubjet'),
        'pubjet-backlinks-hints'     => esc_html__('Using this widget, you can display backlinks in different parts of your website', 'pubjet'),
        'use-google-translate'       => esc_html__('Use Google Translate for English Slug', 'pubjet'),
        'use-google-translate-hints' => esc_html__('By default, the URL of the reportage post is generated from the reportage title in Persian, if you want to use the Google Translate service to English the slug and the URL, activate this option. Please note that activating this option will reduce the speed of publishing the reportage.', 'pubjet'),
        'version'                    => esc_html__('Version', 'pubjet'),
        'username'                   => esc_html__('Username', 'pubjet'),
        'displayname'                => esc_html__('Display Name', 'pubjet'),
        'author-name'                => esc_html__('Author Name', 'pubjet'),
        'manual-approve'             => esc_html__('Manual Approve', 'pubjet'),
        'manual-approve-hints'       => esc_html__('Enable this option if you want to review the reportage manually after review', 'pubjet'),
        'pmk-hints'                  => esc_html__('This feature is useful when you want to make Pubjet compatible with other plugins that perform actions on the text menu. By using this feature, users can easily add specific information and metadata to reports without the need for fundamental changes in other plugins and benefit from better integration and coordination between plugins.', 'pubjet'),
        'save'                       => esc_html__('Save', 'pubjet'),
        'delete'                     => esc_html__('Delete', 'pubjet'),
        'actions'                    => esc_html__('Actions', 'pubjet'),
        'keyname'                    => esc_html__('Key Name', 'pubjet'),
        'keyvalue'                   => esc_html__('Key Value', 'pubjet'),
        'define-post-metakeys'       => esc_html__('Defining Custom Metakeys for Posts', 'pubjet'),
        'add-metakey'                => esc_html__('Add Metakey', 'pubjet'),
        'metakeys'                   => esc_html__('Metakeys', 'pubjet'),
        'delete-first-image'         => esc_html__('Delete First Image', 'pubjet'),
        'align-center-images'        => esc_html__('Align Center Images', 'pubjet'),
        'align-center-images-help'   => esc_html__('If you want all the images in the reports to be displayed in the middle of the fold, activate this option. Please note that this option is only applied to reports and other writings are ignored.', 'pubjet'),
        'title'                      => esc_html__('Plan Name', 'pubjet'),
        'category'                   => esc_html__('Category', 'pubjet'),
        'pricing-plans'              => esc_html__('Pricing Plans', 'pubjet'),
        'plans-categories'           => esc_html__('Plans Categories', 'pubjet'),
        'check-token'                => esc_html__('Check Token', 'pubjet'),
        'default-category'           => esc_html__('Default Category', 'pubjet'),
        'triboon-token'              => esc_html__('Access Token', 'pubjet'),
        'congratulation'             => esc_html__('Congratulations !', 'pubjet'),
        'valid-token'                => esc_html__('The Access Token is Valid', 'pubjet'),
        'invalid-token'              => esc_html__('The Access Token is Invalid', 'pubjet'),
        'pubjet'                     => esc_html__('Pubjet', 'pubjet'),
        'reportage'                  => esc_html__('Reportage', 'pubjet'),
        'enable'                     => esc_html__('Enable', 'pubjet'),
        'disable'                    => esc_html__('Disable', 'pubjet'),
        'copy'                       => esc_html__('Copy', 'pubjet'),
        'copied'                     => esc_html__('Copied !', 'pubjet'),
        'uninstall'                  => esc_html__('Clearing Plugin Data After Deletion', 'pubjet'),
        'no'                         => esc_html__('No', 'pubjet'),
        'yes'                        => esc_html__('Yes', 'pubjet'),
        'delete-log'                 => esc_html__('Delete Log', 'pubjet'),
        'delete-log-confirm'         => esc_html__('Are you sure to delete the log file?', 'pubjet'),
        'reload'                     => esc_html__('Refresh', 'pubjet'),
        'refresh-data'               => esc_html__('Refresh Data', 'pubjet'),
        'error-occured'              => esc_html__('An error has occurred. Try again', 'pubjet'),
        'post-not-found'             => esc_html__('Post not found', 'pubjet'),
        'rep-not-found'              => esc_html__('Reportage not found', 'pubjet'),
        'post-not-reportage'         => esc_html__('Unfortunately, the operation was not done. This post is not a reportage', 'pubjet'),
        'delete-permission-limit'    => esc_html__('Error deleting file. File deletion access is restricted from the host side', 'pubjet'),
        'missing-params'             => esc_html__('Some required parameters were not sent with the request', 'pubjet'),
        'permission-error'           => esc_html__('You do not have access to perform this operation', 'pubjet'),
        'missing-token'              => esc_html__('Please enter the access token in the pubjet plugin settings menu', 'pubjet'),
        'invalid-http-method'        => esc_html__('The http request method is wrong', 'pubjet'),
        'empty-reportage-content'    => esc_html__('The content of the reportage is empty', 'pubjet'),
        'general'                    => esc_html__('General', 'pubjet'),
        'debug'                      => esc_html__('Debugging', 'pubjet'),
        'enable-debugging'           => esc_html__('Enable Debugging', 'pubjet'),
        'advanced'                   => esc_html__('Advanced', 'pubjet'),
        'saved'                      => esc_html__('Saved !', 'pubjet'),
        'reportage-data'             => esc_html__('Pubjet :: Reportage Data', 'pubjet'),
        'reportage-options'          => esc_html__('Pubjet :: Reportage Options', 'pubjet'),
        'enable-nofollow'            => esc_html__('Enable NoFollow Links', 'pubjet'),
        'pwait'                      => esc_html__('Please Wait ...', 'pubjet'),
        'modules'                    => esc_html__('Modules', 'pubjet'),
        'curl-module'                => esc_html__('cUrl', 'pubjet'),
        'openssl-module'             => esc_html__('OpenSSL', 'pubjet'),
        'required-modules'           => esc_html__('Required Modules', 'pubjet'),
        'required-modules-help'      => esc_html__('Pubjet plugin requires the activation of the following modules for its proper functioning. If any of the following items are not active, ask your hosting support to activate the inactive items for you.', 'pubjet'),
        'check-now'                  => esc_html__('Check  Now', 'pubjet'),
        'update-settings'            => esc_html__('Update Settings', 'pubjet'),
        'settings-saved'             => esc_html__('Settings saved successfully', 'pubjet'),
        'gateway-error'              => esc_html__('Gateway 504 error', 'pubjet'),
        'pubjet-token'               => esc_html__('Pubjet Token', 'pubjet'),
        'enter-token-desc'           => esc_html__('Pubjet plugin needs an access token to work properly. Please enter the access token in the plugin settings.', 'pubjet'),
        'remindme-later'             => esc_html__('Remindme Later', 'pubjet'),
        'permanent-hide'             => esc_html__('Permanent Hide', 'pubjet'),
        'select-categories-hints'    => esc_html__('By default, all your categories are sent to Triboon to determine the reportage category correctly. If you only want the reportage to be published in certain categories, select them in this section.', 'pubjet'),
        'categories'                 => esc_html__('Categories', 'pubjet'),
        'sync-categories'            => esc_html__('Sync Categories', 'pubjet'),
        'sync'                       => esc_html__('Synchronize', 'pubjet'),
        'select-rep-author'          => esc_html__('Select Reportage Post Author', 'pubjet'),
        'select-rep-author-hints'    => esc_html__('By default, when Pabjet publishes a reportage on your website, it uses the account of the site administrator as the author of the reportage, if you want to use another author, select it.', 'pubjet'),
    ]);
}

/**
 * @return array
 */
function pubjet_find_authors() {
    $args = [
        'role__in' => ['author', 'administrator'],
        'orderby'  => 'ID',
        'order'    => 'ASC',
    ];

    $users = get_users($args);

    $result = [];

    foreach ($users as $user) {
        $result[] = [
            'ID'           => $user->ID,
            'user_login'   => $user->user_login,
            'display_name' => $user->display_name,
        ];
    }
    /**
     * The pubjet_authors_and_admins filter.
     *
     * @since 1.0.0
     */
    return apply_filters('pubjet_authors', $result);
}

/**
 * @param $key
 * @param $default
 *
 * @return mixed|null
 */
function pubjet_get_server_var($key, $default = null) {
    $key_upper = strtoupper($key);
    $key_lower = strtolower($key);

    if (isset($_SERVER[$key_upper])) {
        return $_SERVER[$key_upper];
    } elseif (isset($_SERVER[$key_lower])) {
        return $_SERVER[$key_lower];
    } else {
        // بررسی وجود متغیر با پیشوند HTTP_
        $http_key_upper = 'HTTP_' . $key_upper;
        $http_key_lower = 'http_' . $key_lower;
        if (isset($_SERVER[$http_key_upper])) {
            return $_SERVER[$http_key_upper];
        } elseif (isset($_SERVER[$http_key_lower])) {
            return $_SERVER[$http_key_lower];
        }
        return $default;
    }
}

/**
 * @param $key
 *
 * @return false|mixed|string
 */
function pubjet__($key) {
    $strings = pubjet_strings();
    return pubjet_isset_value($strings[$key], $key);
}

/**
 * @return string
 */
function pubjet_get_request_method() {
    return strtoupper(pubjet_isset_value($_SERVER['REQUEST_METHOD']));
}

/**
 * @return boolean
 */
function pubjet_show_copyright() {
    global $pubjet_settings;
    $status = pubjet_isset_value($pubjet_settings[EnumOptions::CopyrightStatus]);
    if (empty($status)) {
        return true;
    }
    return $status !== 'hide';
}

/**
 * @param $data
 *
 * @return array
 */
function pubjet_array($data) {
    if (!$data) {
        return $data;
    }
    return is_array($data) ? $data : [$data];
}

/**
 * @return boolean|WP_Error
 */
function pubjet_is_request_token_valid() {
    if (empty(pubjet_token())) {
        return new \WP_Error('missing-token', pubjet__('missing-token'));
    }
    $request_token = pubjet_get_server_var('authorization');
    if ($request_token === pubjet_token()) {
        return true;
    }
    return new WP_Error('invalid-token', pubjet__('invalid-token'));
}

/**
 * @param $token
 *
 * @return array|boolean|WP_Error
 */
function pubjet_find_token_details($token) {
    /**
     * The pubjet_check_token_url filter.
     *
     * @since 1.0.0
     */
    $url = apply_filters('pubjet_check_token_url', pubjet_api_root() . '/external/wp/token-validation/', $token);

    $headers = [
        'Content-Type'  => 'application/json',
        'Authorization' => 'Token ' . trim($token),
    ];

    $result = pubjet_request($url, EnumHttpMethods::GET, $headers);
    pubjet_log('====== Check Token ======');
    pubjet_log($headers);
    pubjet_log($result);
    pubjet_log('====== END Check Token ======');

    if (is_wp_error($result)) {
        return new \WP_Error('error', $result->get_error_message());
    }

    if (isset($result['code']) && 403 == $result['code']) {
        return new \WP_Error('error', pubjet__('invalid-token'));
    }

    if (!isset($result['body']->is_valid)) { // Some error occured
        return new \WP_Error('error', pubjet__('error-occured'));
    }

    // Check if token is valid or not
    if (!$result['body']->is_valid) {
        return new \WP_Error('error', pubjet__('invalid-token'));
    }

    // we must remove "Test" plan in production
    $pricing_plans = pubjet_isset_value($result['body']->pricing_plans, []);
    if ($pricing_plans) {
        $final_plans = [];
        foreach ($pricing_plans as $plan) {
            if ($plan->title !== 'تست پابجت') {
                $final_plans[] = $plan;
            }
        }
        $pricing_plans = $final_plans;
    }

    return [
        'valid'         => true,
        'first_name'    => pubjet_isset_value($result['body']->publisher->first_name),
        'last_name'     => pubjet_isset_value($result['body']->publisher->last_name),
        'phone'         => pubjet_isset_value($result['body']->publisher->phone),
        'email'         => pubjet_isset_value($result['body']->publisher->email),
        'pricing_plans' => $pricing_plans,
        'website_id'    => pubjet_isset_value($result['body']->website_id),
        'website_url'   => pubjet_isset_value($result['body']->website_url),
    ];
}

/**
 * @return bool
 */
function pubjet_notify_version($version = false, $update = false) {
    if (!function_exists('PUBJ')) {
        return false;
    }
    $plugin_version = PUBJ()->getVersion();
    if ($version) {
        $plugin_version = $version;
    }
    if (empty($plugin_version)) {
        return false;
    }

    /**
     * The pubjet_should_notify_version filter.
     *
     * @since 1.0.0
     */
    if (!apply_filters('pubjet_should_notify_version', $version, $update)) {
        return false;
    }

    /**
     * The pubjet_notify_version_request_headers filter.
     *
     * @since 1.0.0
     */
    $headers = apply_filters('pubjet_notify_version_request_headers', [
        'Content-Type'  => 'application/json',
        'Authoriztaion' => 'Token ' . pubjet_token(),
    ]);

    /**
     * The pubjet_notify_version_request_data filter.
     *
     * @since 1.0.0
     */
    $data = apply_filters('pubjet_notify_version_request_data', [
        'version' => $plugin_version,
        'update'  => $update,
    ]);

    $url = apply_filters('pubjet_notify_version_url', pubjet_api_root() . '/external/wp/pubjet/track');

    pubjet_request($url, 'POST', $headers, json_encode($data), [
        'data_format' => 'body',
    ]);
}

/**
 * @param $url
 * @param $method
 * @param $headers
 * @param $body
 *
 * @return \WP_Error|array
 */
function pubjet_request($url, $method = 'GET', $headers = [], $body = [], $pargs = []) {
    $args = [
        'method'    => $method,
        'headers'   => $headers,
        'body'      => $body,
        'sslverify' => false,
    ];

    if ($pargs) {
        $args = array_merge($args, $pargs);
    }

    $response = wp_remote_request($url, $args);

    if (is_wp_error($response)) {
        return $response;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = json_decode(wp_remote_retrieve_body($response));

    return ['code' => $response_code, 'body' => $response_body];
}

/**
 * @param $post_md5
 *
 * @return boolean|integer
 */
function pubjet_is_post_exists($post_md5) {
    global $wpdb;
    $query  = "SELECT `post_id` FROM {$wpdb->postmeta} WHERE `meta_key` = 'duplicate_guard' AND `meta_value` = %s";
    $pquery = $wpdb->prepare($query, $post_md5);
    return $wpdb->get_var($pquery);
}

/**
 * @return boolean
 */
function pubjet_gateway_error($post_content) {
    $result = strpos($post_content, 'try again') === false;
    return !$result;
}

/**
 * @param $condition
 * @param $func_or_string
 */
function pubjet_render_condition($condition, $func_or_string) {
    if (!$condition) {
        return;
    }
    if (is_callable($func_or_string)) {
        call_user_func($func_or_string);

        return;
    }
    echo $func_or_string;
}

/**
 * @param $parent_id
 *
 * @return array
 */
function pubjet_find_wp_categories($parent_id = 0, $hierarchy = true) {
    if ($hierarchy) {
        $categories = get_categories([
                                         'parent'     => $parent_id,
                                         'hide_empty' => false,
                                     ]);

        $categories_list = [];

        foreach ($categories as $category) {
            $category_item = [
                'name' => $category->name,
                'id'   => $category->term_id,
                'slug' => $category->slug,
            ];

            $children = pubjet_find_wp_categories($category->term_id, $hierarchy);

            if (!empty($children)) {
                $category_item['children'] = $children;
            }

            $categories_list[] = $category_item;
        }

        return $categories_list;
    }

    // Flat
    $result     = [];
    $categories = get_categories(['hide_empty' => false,]);
    pubjet_log($categories);
    foreach ($categories as $category) {
        $result[] = [
            'id'   => $category->term_id,
            'name' => $category->name,
            'slug' => $category->slug,
        ];
    }

    return $result;
}

/**
 * @return array
 */
function pubjet_find_wp_tags() {
    $result = [];
    $tags   = get_tags(['hide_empty' => false,]);
    foreach ($tags as $tag) {
        $result[] = [
            'id'   => $tag->term_id,
            'name' => $tag->name,
        ];
    }

    return $result;
}


/**
 * @since 1.0.0
 */
function pubjet_sync_categories() {
    global $pubjet_settings;


    /**
     * The pubjet_before_sync_categories action.
     *
     * @since 1.0.0
     */
    do_action('pubjet_before_sync_categories');

    if (pubjet_isset_value($pubjet_settings['categories'])) {
        $categories     = [];
        $categories_ids = array_map('trim', explode(',', $pubjet_settings['categories']));
        foreach ($categories_ids as $category_id) {
            $category = get_category($category_id);
            if ($category && !is_wp_error($category)) {
                $categories[] = [
                    'title'       => $category->name,
                    'unique_name' => $category->slug,
                ];
            }
        }
    } else {
        $categories = pubjet_find_wp_categories(false, false);

        if ($categories) {
            $categories = array_map(function ($item) {
                return [
                    'title'       => $item['name'],
                    'unique_name' => $item['slug'],
                ];
            }, $categories);
        }
    }

    pubjet_log($categories);

    /**
     * The pubjet_sync_category_sync filter.
     *
     * @since 1.0.0
     */
    $url = apply_filters('pubjet_sync_category_sync', pubjet_api_root() . '/external/wp/relative-category/', pubjet_token());

    if (pubjet_is_dev_mode()) {
        return;
    }

    $response = pubjet_request($url, 'POST', [
        'Content-Type'  => 'application/json; charset=utf-8',
        'Authorization' => 'Token ' . pubjet_token(),
    ],                         json_encode(['categories' => $categories,]), ['data_format' => 'body',]);

    pubjet_log($response);
    return $response;
}

/*
 * @return array
 */
function pubjet_default_settings() {
    /**
     * The pubjet_default_settings filter.
     *
     * @since 1.0.0
     */
    return apply_filters('pubjet_default_settings', [
        'token'                    => '',
        'defaultCategory'          => '',
        'debug'                    => '',
        'alignCenterImages'        => '',
        'nofollow'                 => '',
        'lastCategoriesSyncTime'   => '',
        'activationVersion'        => '',
        'copyrightStatus'          => '',
        'uninstallCleanup'         => '',
        'lastCheckingMissedPosts'  => '',
        'pricingPlans'             => [],
        'manualApprove'            => false,
        'useGoogleTranslate'       => false,
        'processDataByQueryString' => false,
    ]);
}

/**
 * @return array
 */
function pubjet_settings() {
    /**
     * The pubjet_default_settings filter.
     *
     * @since 1.0.0
     */
    $default_settings = pubjet_default_settings();
    $settings         = get_option(EnumOptions::Settings, []);
    $settings         = pubjet_parse_args($settings, $default_settings);

    /**
     * The pubjet_settings filter.
     *
     * @since 1.0.0
     */
    return apply_filters('pubjet_settings', $settings);
}

/**
 * @param $option_name
 * @param $option_value
 *
 * @return bool
 */
function pubjet_update_setting($option_name, $option_value) {
    $settings               = pubjet_settings();
    $settings[$option_name] = $option_value;
    return update_option(EnumOldOptions::Settings, $settings);
}

/**
 * @param $terms
 * @param $method
 *
 * @return void
 */
function pubjet_sync_category($terms, $method = 'POST') {
    $terms = is_array($terms) ? $terms : [$terms];
    /**
     * The pubjet_sync_category_sync filter.
     *
     * @since 1.0.0
     */
    $url = apply_filters('pubjet_sync_category_sync', pubjet_api_root() . '/external/wp/relative-category/', pubjet_token());

    if (pubjet_is_dev_mode()) {
        $url = 'https://api-staging.triboon.net/external/wp/relative-category/';
    }
    return pubjet_request($url, $method, [
        'Content-Type'  => 'application/json',
        'Authorization' => 'Token ' . trim(pubjet_token()),
    ],                    json_encode([
                                          'categories' => $terms,
                                      ]));
}

/**
 * @param $message
 * @param $extra
 *
 * @return \Sentry\EventId|null
 */
function pubjet_log_sentry($message, $extra = []) {
    $extra = pubjet_parse_args($extra, [
        'website_url'    => site_url(),
        'website_title'  => get_bloginfo('name'),
        'pubjet_version' => PUBJ()->getVersion(),
    ]);
    \Sentry\init(['dsn' => 'https://ea145de5084460f189b3bf2d66b6af06@sentry.hamravesh.com/6647',]);
    \Sentry\configureScope(function (Scope $scope) use ($extra) {
        foreach ($extra as $key => $value) {
            $scope->setExtra($key, $value);
        }
    });
    return \Sentry\captureException(new Exception($message));
}

/**
 * @return boolean
 */
function pubjet_should_publish_reportage_manually() {
    global $pubjet_settings;
    return pubjet_isset_value($pubjet_settings['manualApprove']);
}

/**
 * @return array
 */
function pubjet_find_backlink_positions() {
    /**
     * The pubjet_backlink_positions filter.
     *
     * @since 1.0.0
     */
    return apply_filters('pubjet_backlink_positions', [
        'footer_inner',
        'footer_main',
        'footer_all',
        'sidebar_inner',
        'sidebar_main',
        'sidebar_all',
        'header_inner',
        'header_main',
        'header_all',
    ]);
}

/**
 * @param $status
 *
 * @return string
 */
function pubjet_send_plugin_status_to_api($status) {
    if (empty(trim(pubjet_token()))) { // Check if user enter token or not
        return;
    }
    $settings     = pubjet_settings();
    $url          = 'https://api.triboon.net/external/wp/pubjet-info/';
    $request_data = [
        'status'                   => $status,
        'pubjet_version'           => PUBJ()->getVersion(),
        'backlink_recipient_path'  => pubjet_isset_value($settings['processDataByQueryString']) ? '?action=' . EnumActions::CreateBacklink : rest_get_url_prefix() . '/pubjet/v1/backlink',
        'reportage_recipient_path' => pubjet_isset_value($settings['processDataByQueryString']) ? '?action=' . EnumActions::CreateReportage : rest_get_url_prefix() . '/pubjet/v1/reportage',
    ];
    pubjet_log($request_data);
    $response = wp_remote_post($url, [
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . pubjet_token(),
        ],
        'method'  => 'POST',
        'body'    => json_encode($request_data),
    ]);
    // بررسی پاسخ API برای اشکالات احتمالی
    if (is_wp_error($response)) {
        pubjet_log_sentry('Error sending status to API: ' . $response->get_error_message());
    }
    $result = wp_remote_retrieve_body($response);
    pubjet_log($result);
    return $result;
}


/**
 * @param $request_date
 *
 * @return string|WP_Error
 */
function pubjet_find_mysql_date_by_request_date($request_date) {
    try {
        $dt = new DateTime($request_date);
        $dt->setTimezone(new DateTimeZone(wp_timezone_string()));
        return $dt->format('Y-m-d H:i:s');
    } catch (\Exception $ex) {
        return new WP_Error('error-occured', $ex->getMessage());
    }
}

/**
 * @return DBLoader
 */
function pubjet_db() {
    return DBLoader::getInstance();
}

/**
 * @return mixed|null
 */
function pubjet_http_json_request_headers() {
    /*
     * The pubjet_http_json_requests filter.
     *
     * @since 1.0.0
     */
    return apply_filters('pubjet_http_json_requests', [
        'Content-Type'  => 'application/json',
        'Authorization' => 'api-key ' . pubjet_token(),
    ]);
}

/**
 * @param $post_ID
 * @param $reportage_ID
 *
 * @return array|void
 */
function pubjet_publish_reportage($post_id, $reportage_id) {
    $url = pubjet_api_root() . '/external/wp/reportages/' . $reportage_id . '/publish';
    pubjet_log($url);

    if (pubjet_is_dev_mode()) {
        return;
    }

    $args = [
        'headers'     => [
            'Authorization' => 'api-key ' . pubjet_token(),
            'Content-Type'  => 'application/json',
        ],
        'method'      => 'POST',
        'data_format' => 'body',
        'body'        => json_encode(['url' => get_permalink($post_id)]),
    ];

    $response = wp_remote_post($url, $args);

    pubjet_log([$response, $url, $args]);

    return [
        'code' => wp_remote_retrieve_response_code($response),
        'body' => json_decode(wp_remote_retrieve_body($response), true),
    ];
}

/**
 * @param $backlink_id
 *
 * @return array|WP_Error
 */
function pubjet_publish_backlink_request($backlink_id) {
    $url = pubjet_api_root() . '/external/wp/backlink/confirm';
    pubjet_log($url);

    if (pubjet_is_dev_mode()) {
        return new WP_Error('development-mode', pubjet__('dev-mode'));
    }

    $result = pubjet_request($url, 'POST', pubjet_http_json_request_headers(), json_encode(['id' => $backlink_id, 'status' => 'publisher_published',]), ['data_format' => 'body']);

    pubjet_log($result);

    return $result;
}