<?php
// Exit if accessed directly
if ( !defined ( 'ABSPATH' ) )
    exit;

// Return if already defined
if ( defined ( 'LIVE_ADMIN_VERSION' ) )
    return;

/**
 * Version number
 *
 * @since 0.1
 */
define ( 'LIVE_ADMIN_VERSION', '0.2.3' );

/**
 * PATHs and URLs
 *
 * @since 0.1
 */
define ( 'LIVE_ADMIN_DIR', plugin_dir_path ( __FILE__ ) );
define ( 'LIVE_ADMIN_URL', plugin_dir_url ( __FILE__ ) );
define ( 'LIVE_ADMIN_INC_URL', LIVE_ADMIN_URL . '_inc/' );

/**
 * Requires and includes
 */
require_once ( LIVE_ADMIN_DIR . 'functions.php' );
require_once ( LIVE_ADMIN_DIR . 'live-admin-ajax.php' );
require_once ( LIVE_ADMIN_DIR . 'lib/class.settings.php' );
require_once ( LIVE_ADMIN_DIR . 'lib/class.wp-help-pointers.php' );

class WP_LiveAdmin
{
        var $info_notice    = '',
            $info_content   = '',

            /**
             * Admin notice
             */
            $admin_notice   = '',

            /**
             * First url for the iframe
             */
            $iframe_url     = '',

            $buttons        = array(),

            /**
             * Enable admin menu
             */
            $menu           = false,

            /**
             * Enable screen options menu
             */
            $screen_options = false,

            /**
             * Start with sidebar collapsed
             */
            $collapsed      = false,

            /**
             * Disable bundled iFrame loading code
             */
            $override_iframe_loader
                            = false,

            /**
             * Disables following of links within the iFrame
             */
            $disable_nav    = false,

            /**
             * Disables live admin's listeners
             */
            $disable_listeners
                            = false,

            /**
             * Disables admin notices
             */
            $disable_admin_notices
                            = false,

            /**
             * Allow browsing to links on the same domain (don't open in new window)
             */
            $allow_same_domain_links
                            = false,

            /**
             * Remember the sidebar state (collapsed|expanded)
             */
            $remember_sidebar_state
                            = false,

            /**
             * Custom JS vars
             */
            $custom_js_vars = array(),

            $handle = '',

            /**
             * Minimum user capability
             */
            $capability = 'edit_posts',

            $postbox_class  = 'postbox-live';

        public function _register() {
            global $handle;
            $handle = $this->handle;

            $this->check_permissions();

            $this->enqueue_live_admin_styles_and_scripts();
            $this->enqueue_styles_and_scripts();
            $this->actions_and_filters();

            if ( empty ( $this->iframe_url ) )
                $this->iframe_url = get_bloginfo('wpurl');

            if ( $this->menu )
                require_once ( LIVE_ADMIN_DIR . 'live-menu/live-menu.php' );

            if ( $this->screen_options ) {
                require_once ( LIVE_ADMIN_DIR . 'live-screen-options/live-screen-options.php' );
            }

            if ( $this->remember_sidebar_state )
                $this->collapsed = $this->get_saved_sidebar_state( true );

            require_once ( LIVE_ADMIN_DIR . 'live-admin-template.php' );
            exit;
        }

        private function check_permissions() {
            if ( ! current_user_can( $this->capability ) )
                wp_die( __( 'Cheatin&#8217; uh?' ) );
        }

        protected function get_saved_sidebar_state( $boolean = true) {
            global $pagenow;

            $user_id = get_current_user_id();
            $state = get_user_meta($user_id, "sidebarstate_$pagenow", true);

            if ( ! $boolean ) return $state;

            return ( 'collapsed' == $state ) ? true : false;
        }

        private function enqueue_live_admin_styles_and_scripts() {
            wp_enqueue_script( 'live-admin', LIVE_ADMIN_INC_URL . 'js/live-admin.js', array ( 'jquery' ), LIVE_ADMIN_VERSION );
            wp_enqueue_style( 'live-admin', LIVE_ADMIN_INC_URL . 'css/live-admin.css', array ( 'customize-controls'), LIVE_ADMIN_VERSION );

            if ( ! empty ( $this->custom_js_vars ) )
                wp_localize_script ( 'live-admin', 'liveAdmin', $this->custom_js_vars );
        }

        protected function actions_and_filters() {
            add_action ( 'live_admin_start', array ( &$this, 'do_start' ) );

            add_action ( 'live_admin_buttons', array ( &$this, 'do_buttons' ), 15 );

            add_action ( 'live_admin_info', array ( &$this, 'do_info' ) );

            if ( ! empty ( $this->pointers ) )
                add_action('live_admin_init', array (&$this, 'pointers' ) );

            add_action ( 'live_admin_controls', array ( &$this, 'do_controls' ) );

            add_action ( 'live_admin_footer_actions', array ( &$this, 'do_footer_actions' ) );

        }

        /**
         * Methods to be overwritten by child classes
         */
        public function do_start() {}

        public function do_controls() {}

        public function do_footer_actions() {}

        protected function enqueue_styles_and_scripts() {}

        public function do_buttons() {
            ksort ( $this->buttons );
            foreach ( $this->buttons as $button ) {
                echo $button;
            }
            unset ( $this->buttons );
        }

        public function add_button( $button, $priority = 10 ) {
            while ( isset ( $this->buttons[$priority] ) ) {
                $priority++;
            }

            $this->buttons[$priority] = $button;
        }

        public function do_info() {
            ?>
            <div id="live-admin-info" class="customize-section open">
                <?php if ( ! empty ( $this->info_notice ) ) : ?>
                <div class="customize-section-title" aria-label="<?php esc_attr_e( 'Live Admin', 'live-admin' ); ?>" tabindex="0">
                    <span class="preview-notice">

                        <?php echo $this->info_notice; ?>

                    </span>
                </div>

                <?php if ( ! empty ( $this->info_content ) ) : ?>

                    <div class="customize-section-content">
                        <div class="theme-description">

                            <?php echo $this->info_content; ?>

                        </div>

                    </div>

                <?php endif; ?>
                <?php endif;?>

                <?php if ( ! $this->disable_admin_notices ) : ?>

                    <?php $this->do_notices(); ?>

                <?php endif; ?>

            </div>

            <?php
        }

        protected function do_notices() {
            if ( is_network_admin() )
                    do_action('network_admin_notices');
            elseif ( is_user_admin() )
                    do_action('user_admin_notices');
            else
                    do_action('admin_notices');

            do_action('all_admin_notices');

            if ( !empty ( $this->admin_notice ) )
                echo $this->admin_notice;
        }

        public function pointers() {
            $pointers = apply_filters( 'live_admin_pointers', $this->pointers );
            new WP_Help_Pointer($pointers);
        }


        /**
         * A couple re-usable template items
         */
        public function logout_button() {
            return '<a href="' . wp_logout_url() . '" class="button" id="log-out">' . __("Log out") . '</a>';
        }

        public function switch_button( $text = '' ) {
            global $live_admin_settings;
            return '<a href="' . $live_admin_settings->switch_url() . '" class="button" id="log-out">' . __("Switch Interface", 'live-admin') . '</a>';
        }

        public function switch_url() {
            if ( empty ( $this->switch_url ) ) {
                global $live_admin_settings;
                $this->switch_url = $live_admin_settings->switch_url();
            }

            return $this->switch_url;
        }

        public function my_account_button() {
            $user_id      = get_current_user_id();
            $current_user = wp_get_current_user();
            $profile_url  = get_edit_profile_url( $user_id );

            if ( ! $user_id )
                return;

            $avatar = get_avatar( $user_id, 16 );
            $howdy  = sprintf( __('Howdy, %1$s'), $current_user->display_name );
            $class  = empty( $avatar ) ? '' : ' with-avatar';

            $link = sprintf ('<a href="%1$s" title="%2$s" class="howdy %3$s">%4$s%5$s</a>',
                        $profile_url,
                        __('My Account'),
                        $class,
                        $howdy,
                        $avatar
                    );

            return $link;

        }

        public function get_displayed_post_id() {

        }

}

function live_admin_register_extension( $live_admin_extension_class ) {
    global $live_admin;

	if ( !class_exists( $live_admin_extension_class ) )
		return false;

	$live_admin = new $live_admin_extension_class;
    $live_admin->_register();
}