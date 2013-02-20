<?php
if ( ! defined( 'ABSPATH' ) )
    exit(-1);

if ( ! class_exists ( 'WP_LiveAdmin_Settings' ) ) :

    class WP_LiveAdmin_Settings
    {

        public $current_user_settings = array(),
                $is_default = null,
                $is_active = null,
                $handle = '',
                $option_name = '',
                $option_description = '',
                $default = 'false',
                $screen = '';

        public function __construct( $handle = '', $option_name = '', $option_description = '', $default = 'false', $screen = '' ) {
            $this->handle = $handle;
            $this->option_name = $option_name;
            $this->option_description = $option_description;

            // Network defaults have preference over plugin defaults
            if ( is_multisite() ) {
                $network_defaults = (array) get_site_option( 'live-admin-defaults' );
                if ( isset ($network_defaults[$this->handle] ) )
                    $default = $network_defaults[$this->handle];
            }

            $this->default = $default;
            if ( is_array ( $screen ) )
                $this->screen = $screen;
            else
                $this->screen = array ( ( empty ( $screen ) ) ? "$handle.php" : $screen );

            $this->current_user_settings();
            $this->actions_and_filters();
        }

            /**
             * PHP4
             */
            public function wp_liveadmin_settings() {
                $this->_construct();
            }

        /**
         * Sets the default setting for handle
         *
         * @param array (optional) $current_user_settings
         * @return array $settings
         */
        public function set_default_settings( $current_user_settings = null ) {
            $user_id = get_current_user_id();

            $settings = array ( $this->handle => $this->default );

            if ( is_array ( $current_user_settings ) )
                $settings = shortcode_atts ( $settings, $current_user_settings );

            update_user_meta ( $user_id, 'live-admin', $settings );

            return $settings;
        }

        /**
         * Returns the setting for the current user
         *
         * @return mixed $setting
         */
        public function get_current_user_settings() {
            return ( empty ( $this->current_user_settings ) ) ? $this->current_user_settings() : $this->current_user_settings;
        }

            /**
             * Retrieves and sets class vars for current user settings, sets defaults if necessary
             *
             * @uses apply_filters 'live_admin_user_setting_' . $handle
             * @return mixed The settings
             */
            private function current_user_settings() {
                $current_user_settings = get_user_meta( get_current_user_id(), 'live-admin', true );

                if ( empty ( $current_user_settings ) || ! is_array ( $current_user_settings ) || ! isset ( $current_user_settings[$this->handle] ) )
                    $current_user_settings = $this->set_default_settings();

                $this->current_user_settings = $current_user_settings;
                $this->current_user_setting = $current_user_settings[$this->handle];

                return apply_filters( 'live_admin_user_setting_' . $this->handle, $this->current_user_setting );
            }

        /**
         * Boolean function to check if live is the default for this handle
         * @return boolean
         */
        public function is_default() {
            if ( ! is_null ( $this->is_default ) )
                return $this->is_default;

            if ( empty ( $this->current_user_setting ) ) {
                _doing_it_wrong ( 'WP_LiveAdmin_Settings::is_default', "Don't ask if a Live Admin module is default for the current user before set_current_user has run", "0.1" );
                return false;
            }

            $this->is_default = ( $this->current_user_setting == 'true' ) ? true : false;

            $this->is_default  = apply_filters ( 'live_admin_is_default-' . $this->handle, $this->is_default );
            return $this->is_default;
        }

        public function is_active () {
            if ( ! is_null ( $this->is_active ) )
                return $this->is_active;

            global $pagenow;
            if ( ! in_array ( $pagenow, $this->screen ) )
                return $this->is_active = false;

            // We are at the right page, let's globalize Live Admin settings for use in our live admin interface
            global $live_admin_settings;
            $live_admin_settings = $this;

            $is_default = $this->is_default();
            $is_deactivated = $this->is_deactivated();
            $is_activated = $this->is_activated();

            if ( $is_default )
                $this->is_active = ( ! $is_deactivated );
            elseif ( ! $is_default )
                $this->is_active = ( $is_activated );

            $this->is_active = apply_filters ( 'live_admin_is_active-' . $this->handle, $this->is_active );
            return $this->is_active;
        }

        public function is_activated() {
            return ( isset($_REQUEST['live_' . $this->handle]) && $_REQUEST['live_' . $this->handle] == true  );
        }

        public function is_deactivated() {
            return ( isset($_REQUEST['live_' . $this->handle]) && $_REQUEST['live_' . $this->handle] == false  );
        }


        protected function actions_and_filters() {
            // Check if live_admin_options has been loaded already
            if ( ! has_action ( 'live_admin_options' ) ) {
                add_action( 'personal_options', array ( &$this, 'add_live_admin_settings' ), 99 );
                add_action( 'network_admin_menu', array ( &$this, 'add_network_admin_page' ) );
                add_action('network_admin_edit_live_admin_save', array($this, 'save_network_settings'), 10, 0);
            }

            add_action( 'live_admin_options', array ( &$this, 'add_user_settings' ) );

            add_action( 'personal_options_update', array ( &$this, 'save_user_settings' ) );
            add_action( 'edit_user_profile_update', array ( &$this, 'save_user_settings' ) );
        }

        /**
         * NETWORK ADMIN
         */
        public function add_network_admin_page() {
            add_submenu_page('settings.php', __('Live Admin Default Settings', 'live-admin'), __('Live Admin', 'live-admin'), 'manage_network', 'live_admin_defaults', array ( &$this, 'network_admin_page' ) );
        }

            public function network_admin_page() {
                ?>
                <div class="wrap">
                    <?php if ( isset ( $_GET['updated'] ) && $_GET['updated'] ) : ?>

                        <div class="updated">
                            <p><?php _e('Defaults saved.','live-admin'); ?></p>
                        </div>

                    <?php elseif ( isset ( $_GET['error'] ) && $_GET['error'] ) : ?>

                        <div class="error">
                            <p><?php _e('Something went wrong saving your settings, please try again.','live-admin'); ?></p>
                        </div>

                    <?php endif; ?>

                    <div class="icon32" id="icon-options-general"></div>
                    <h2><?php _e('Live Admin Default Settings','live-admin'); ?></h2>
                    <p><?php _e('The settings below will be the default settings for new users in your site network.','live-admin'); ?></p>

                    <form action="edit.php?action=live_admin_save" method="post">
                        <table class="form-table">
                            <tbody>
                            <?php do_action('live_admin_options', 'network'); ?>
                            </tbody>
                        </table>
                        <p class="submit">
                            <input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes','live-admin'); ?>" />
                        </p>
                    </form>
                </div>
                <?php
            }

        /**
         * USER PROFILES
         */
        public function add_live_admin_settings( $profileuser ) {
            ?>
                </tbody>
            </table>
            <h3><?php _e('Live Admin','live-admin'); ?></h3>
            <table class="form-table">
                <tbody>
            <?php
            do_action('live_admin_options', $profileuser);
        }

        public function add_user_settings( $profileuser ) {
            $displayed_user_settings = ( $profileuser == 'network' ) ? (array) get_site_option( 'live-admin-defaults' ) : (array) get_user_meta( $profileuser->ID, 'live-admin', true );
            if ( ! isset ( $displayed_user_settings[$this->handle] ) )
                $displayed_user_settings[$this->handle] = $this->default;

            $current_setting = $displayed_user_settings[$this->handle];
            ?>
            <tr>
                <th scope="row"><?php echo $this->option_name; ?></th>
                <td>
                    <label for="live_admin_<?php echo $this->handle; ?>">
                        <input type="hidden" name="live_admin_options[<?php echo $this->handle; ?>]" value="false">
                        <input name="live_admin[<?php echo $this->handle; ?>]" type="checkbox" id="live_admin_<?php echo $this->handle; ?>" value="true" <?php checked( 'true', $current_setting ); ?> />
                            <?php echo $this->option_description; ?>
                    </label>
                </td>
            </tr>
            <?php
        }

        public function save_network_settings() {
            // Make sure this only happens once
            if ( defined('LIVE_ADMIN_SAVED' ) && LIVE_ADMIN_SAVED )
                return;

            define ( 'LIVE_ADMIN_SAVED', true );

            if ( $options = $this->process_settings_post() ) {
                update_site_option ('live-admin-defaults', $options);
                wp_redirect(add_query_arg(array('page' => 'live_admin_defaults', 'updated' => 'true'), network_admin_url('settings.php')));
            } else {
                wp_redirect(add_query_arg(array('page' => 'live_admin_defaults', 'error' => 'true'), network_admin_url('settings.php')));
            }
            exit();
        }

        /**
         * Save all Live Admin user settings at once
         */
        public function save_user_settings() {
            // Make sure this only happens once
            if ( defined('LIVE_ADMIN_SAVED' ) && LIVE_ADMIN_SAVED )
                return;

            define ( 'LIVE_ADMIN_SAVED', true );

            $user_id = get_current_user_id();

            if ( $options = $this->process_settings_post() )
                update_user_meta ( $user_id, 'live-admin', $options );
        }

        protected function process_settings_post() {
            $settings = $_POST['live_admin'];
            $options = $_POST['live_admin_options'];

            if ( ! is_array ( $options ) ) {
                return;
            }

            foreach ( $options as $option => &$value ) {
                if ( isset ( $settings[$option] ) )
                    $value = $settings[$option];
            }
            return $options;
        }

        public function save_user_setting( $setting_key, $setting_value, $user_id = false ) {
            $options = (array) $this->current_user_settings;
            $options[$setting_key] = $setting_value;

            if ( ! $user_id )
                $user_id = get_current_user_id();

            update_user_meta ( $user_id, 'live-admin', $options );
        }



        public function add_live_query_arg( $value = 1, $url = null ) {
            if ( is_null ( $url ) )
                return add_query_arg ( 'live_' . $this->handle , $value );
            else return add_query_arg ( 'live_' . $this->handle , $value, $url );
        }

        public function remove_live_query_arg( $url = '' ) {
            return $this->add_live_query_arg(false);
        }

        public function switch_url() {
            if ( $this->is_default ) {
                if ( $this->is_active )
                    return $this->add_live_query_arg(0);
                else
                    return $this->remove_live_query_arg();
            } else {
                if ( $this->is_active )
                    return $this->remove_live_query_arg();
                else
                    return $this->add_live_query_arg();
            }
        }
    }
endif;