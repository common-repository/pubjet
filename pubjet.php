<?php
/*
    Plugin Name: Pubjet
    Description: Pubjet is your assistant in publishing advertisement reportage. In fact, Pubjet allows the automatic publication of reportage.
    Author: Triboon
    Author URI:  https://triboon.net
    License: GPL v2 or later
    License URI: http://www.gnu.org/licenses/gpl-2.0.txt
    Version: 4.1.0
*/

use triboon\pubjet\includes\enums\EnumOldOptions;
use triboon\pubjet\includes\enums\EnumOptions;
use triboon\pubjet\includes\Initializer;

if (!class_exists('Pubjet')) {
    final class Pubjet {

        /*
         * @since 1.0
         */
        private static $instance;

        /**
         * @return object|Pubjet The one true Pubjet
         * @uses      PUBJ::constants() Setup the constants needed.
         * @uses      PUBJ::includes() Include the required files.
         * @see       PUBJ()
         * @since     1.0
         * @static
         * @staticvar array $instance
         */
        public static function instance() {
            if (!isset(self::$instance) && !(self::$instance instanceof Pubjet)) {
                self::$instance = new Pubjet();
                self::$instance->loadTextDomain();
                self::$instance->constants();
                self::$instance->includes();
                self::$instance->setupGlobals();
                self::$instance->init();
            }

            return self::$instance;
        }

        /**
         * @return void
         * @since  1.0
         * @access protected
         */
        public function __clone() {
            // Cloning instances of the class is forbidden.
            _doing_it_wrong(__FUNCTION__, esc_html__('Cheatin&#8217; huh?', 'pubjet'), '1.0');
        }

        /**
         * @return void
         * @since  1.0
         * @access protected
         */
        public function __wakeup() {
            // Unserializing instances of the class is forbidden.
            _doing_it_wrong(__FUNCTION__, esc_html__('Cheatin&#8217; huh?', 'pubjet'), '1.0');
        }

        /**
         * @return void
         * @since  1.0
         * @access public
         */
        private function init() {
            register_activation_hook(__FILE__, [$this, 'onActivation']);
            register_deactivation_hook(__FILE__, [$this, 'onDeactivation']);
            add_action('plugins_loaded', [$this, 'onPluginLoaded'], 15);
        }

        /**
         * @return void
         */
        public function onPluginLoaded() {
            Initializer::getInstance();
        }

        /**
         * @access private
         * @return void
         * @since  1.0
         */
        private function constants() {
            $this->defineConstant('PUBJET_FILE', __FILE__);
            $this->defineConstant('PUBJET_PLUGIN_BASE', plugin_basename(__FILE__));
            $this->defineConstant('PUBJET_DIR', trailingslashit(plugin_dir_path(__FILE__)));
            $this->defineConstant('PUBJET_URL', trailingslashit(plugin_dir_url(__FILE__)));
            $this->defineConstant('PUBJET_CSS_DIR', trailingslashit(plugin_dir_path(__FILE__)) . 'assets/css');
            $this->defineConstant('PUBJET_JS_DIR', trailingslashit(plugin_dir_path(__FILE__)) . 'assets/js');
            $this->defineConstant('PUBJET_TPLS_DIR', trailingslashit(PUBJET_DIR) . 'templates/');
            $this->defineConstant('PUBJET_INC_DIR', PUBJET_DIR . 'includes/');
            $this->defineConstant('PUBJET_LIBS_DIR', PUBJET_DIR . 'includes/libs/');
            $this->defineConstant('PUBJET_VERSION', '1.0');
            $this->defineConstant('PUBJET_PREFIX', 'pubjet_');
            $this->defineConstant('PUBJET_PREFIX_DASH', 'pubjet-');
            $this->defineConstant('PUBJET_MAIN_URL', trailingslashit(plugin_dir_url(__FILE__)));
            $this->defineConstant('PUBJET_ASSETS_URL', trailingslashit(plugin_dir_url(__FILE__)) . "assets/");
            $this->defineConstant('PUBJET_IMAGES_URL', PUBJET_ASSETS_URL . "img/");
            $this->defineConstant('PUBJET_CSS_URL', PUBJET_ASSETS_URL . "css/");
            $this->defineConstant('PUBJET_JS_URL', PUBJET_ASSETS_URL . "js/");
            $this->defineConstant('PUBJET_VERSION', $this->getScriptsVersion());
            $this->defineConstant('PUBJET_DB_VERSION', '1.0.0');
            $this->defineConstant('PUBJET_API_ROOT', 'https://api.triboon.net');
            $this->defineConstant('PUBJET_TBL_NAME', 'pubjet_reportages');
            $this->defineConstant('PUBJET_POST_TYPE', 'post');
            $this->defineConstant('PUBJET_DIR_PATH', plugin_dir_path(__FILE__));
            $this->defineConstant('PUBJET_DIR_URL', plugin_dir_url(__FILE__));
            $this->defineConstant('PUBJET_STATUS_DEFAULT', 'publish');
            $this->defineConstant('PUBJET_DEBUG_MODE', boolval(get_option('pubjet_debug_mode')));
            $this->defineConstant('PUBJET_SHOW_COPYRIGHT', true);
        }

        /**
         * @return void
         * @since 1.0
         */
        private function defineConstant($name, $value) {
            if (!defined($name)) {
                define($name, $value);
            }
        }

        /**
         * @access private
         * @return void
         * @since  1.0
         */
        private function includes() {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            require_once PUBJET_INC_DIR . 'functions.php';
            require_once PUBJET_INC_DIR . 'autoload.php';
            require_once PUBJET_DIR . 'vendor/autoload.php';
        }

        /**
         * @access public
         * @return void
         * @since  1.0
         */
        public function loadTextDomain() {
            $locale = get_locale();
            $locale = str_replace('_', '-', $locale);
            $mo     = 'pubjet-' . $locale . '.mo';
            load_textdomain('pubjet', WP_LANG_DIR . '/pubjet/' . $mo);
            load_textdomain('pubjet', plugin_dir_path(__FILE__) . 'languages/' . $mo);
            load_plugin_textdomain('pubjet');
        }

        /**
         * @return string
         * @since  1.0
         * @author Triboon
         */
        public function getScriptsVersion() {
            if (!function_exists('get_plugin_data')) {
                require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            }
            $plugin_data = get_plugin_data(__FILE__);

            return (defined('WP_ENVIRONMENT') && "development" === WP_ENVIRONMENT) ? time() : $plugin_data['Version'];
        }

        /**
         * @param $plugin_path
         *
         * @return mixed
         */
        public function getVersion() {
            $plugin_data = get_plugin_data(__FILE__);
            return $plugin_data['Version'];
        }

        /**
         * @return void
         */
        private function trackActivationVersion() {
            $activation_option = \triboon\pubjet\includes\enums\EnumOldOptions::ActivationVersion;
            $activation_value  = get_option($activation_option);
            if (!$activation_value) {
                update_option($activation_option, $this->getVersion());
            }
        }

        /**
         * @return void
         */
        private function migrate() {
            $last_token = get_option('triboon_token');
            if ($last_token) {
                $this->migrateOldSettings();
            } else {
                $this->migrateNewSettings();
            }
        }

        /**
         * @return void
         */
        private function migrateOldSettings() {
            // Migrate from 1.0.0 to 2,0,0
            $last_category   = get_option('triboon_default_category');
            $last_debug_mode = get_option('triboon_debug_mode');

            update_option(\triboon\pubjet\includes\enums\EnumOldOptions::Token, sanitize_text_field($last_token));
            update_option(\triboon\pubjet\includes\enums\EnumOldOptions::DefaultCategory, sanitize_text_field($last_category));
            update_option(\triboon\pubjet\includes\enums\EnumOldOptions::DebugMode, boolval($last_debug_mode));

            $deleted_options = [
                'triboon_token',
                'triboon_debug_mode',
                'triboon_default_category',
            ];
            foreach ($deleted_options as $item) {
                delete_option($item);
            }
        }

        /**
         * @return void
         */
        private function migrateNewSettings() {
            $token = get_option(EnumOldOptions::Token);
            if (empty($token)) {
                update_option(EnumOptions::Settings, pubjet_default_settings());
                return;
            }
            $settings = [
                'token'                   => $token,
                'defaultCategory'         => get_option(\triboon\pubjet\includes\enums\EnumOldOptions::DefaultCategory),
                'debug'                   => get_option(\triboon\pubjet\includes\enums\EnumOldOptions::DebugMode),
                'lastCheckingMissedPosts' => get_option(\triboon\pubjet\includes\enums\EnumOldOptions::LastCheckingMissedPosts),
                'copyrightStatus'         => get_option(\triboon\pubjet\includes\enums\EnumOldOptions::CopyrightStatus),
                'uninstallCleanup'        => get_option(\triboon\pubjet\includes\enums\EnumOldOptions::UninstallCleanup),
                'nofollow'                => get_option(\triboon\pubjet\includes\enums\EnumOldOptions::Nofollow),
                'alignCenterImages'       => get_option(\triboon\pubjet\includes\enums\EnumOldOptions::AlignCenterImages),
                'lastCategoriesSyncTime'  => get_option(\triboon\pubjet\includes\enums\EnumOldOptions::LastCategoriesSyncTime),
            ];
            update_option(EnumOldOptions::Settings, $settings);

            // Delete old options
            $options_to_delete = [
                EnumOldOptions::DefaultCategory,
                EnumOldOptions::DebugMode,
                EnumOldOptions::LastCheckingMissedPosts,
                EnumOldOptions::CopyrightStatus,
                EnumOldOptions::UninstallCleanup,
                EnumOldOptions::Nofollow,
                EnumOldOptions::AlignCenterImages,
                EnumOldOptions::LastCategoriesSyncTime,
            ];
            foreach ($options_to_delete as $option) {
                delete_option($option);
            }
        }

        /**
         * @return void
         */
        public function setupGlobals() {
            $GLOBALS['pubjet_settings'] = pubjet_settings();
            $GLOBALS['pubjet_options']  = $GLOBALS['pubjet_settings'];
        }

        /**
         * @return void
         * @since  1.0
         * @author Triboon
         */
        public function onActivation() {
            pubjet_send_plugin_status_to_api('active');
            $this->migrate();
            $this->trackActivationVersion();
            flush_rewrite_rules();
        }

        /**
         * @return void
         * @since  1.0
         * @author Triboon
         */
        public function onDeactivation() {
            pubjet_send_plugin_status_to_api('inactive');
        }

    }
}

/**
 * The main function for that returns Pubjet
 *
 * The main function responsible for returning the one true Pubjet
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $pubjet = PUBJ(); ?>
 *
 * @return object|Pubjet The one true Pubjet Instance.
 * @since 1.0
 */
function PUBJ() {
    return Pubjet::instance();
}

PUBJ();