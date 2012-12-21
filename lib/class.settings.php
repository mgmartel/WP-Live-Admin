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
            $this->default = $default;
            $this->screen = ( empty ( $screen ) ) ? "$handle.php" : $screen;

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
                $settings = array_merge ( $settings, $current_user_settings );

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
             * Retreives and sets class vars for current user settings, sets defaults if necessary
             *
             * @uses apply_filters 'live_admin_user_setting_' . $handle
             * @return mixed The settings
             */
            private function current_user_settings() {
                $current_user_settings = get_user_meta( get_current_user_id(), 'live-admin', true );

                // Is not supposed to happen
                if ( empty ( $current_user_settings ) || ! is_array ( $current_user_settings ) )
                    $current_user_settings = $this->set_default_settings();

                /*if ( ! isset ( $current_user_settings[$this->handle] ) )
                    $current_user_settings = $this->set_default_settings( $current_user_settings );*/

                $this->current_user_settings = $current_user_settings;
                $this->current_user_setting = $current_user_settings[$this->handle];

                return apply_filters( 'live_admin_user_setting_' . $handle, $this->current_user_setting );
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

            return apply_filters ( 'live_admin_is_default-' . $handle, &$this->is_default );
        }

        public function is_active () {
            if ( ! is_null ( $this->is_active ) )
                return $this->is_active;

            global $pagenow;
            if ( $pagenow != $this->screen )
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

            return apply_filters ( 'live_admin_is_active-' . $handle, &$this->is_active );
        }

        public function is_activated() {
            return ( isset($_REQUEST['live_' . $this->handle]) && $_REQUEST['live_' . $this->handle] == true  );
        }

        public function is_deactivated() {
            return ( isset($_REQUEST['live_' . $this->handle]) && $_REQUEST['live_' . $this->handle] == false  );
        }


        protected function actions_and_filters() {
            // Check if live_admin_options has been loaded already
            if ( ! has_action ( 'live_admin_options' ) )
                add_action( 'personal_options', array ( &$this, 'add_live_admin_settings' ) );

            add_action( 'live_admin_options', array ( &$this, 'add_user_settings' ) );

            add_action( 'personal_options_update', array ( &$this, 'save_user_settings' ) );
            add_action( 'edit_user_profile_update', array ( &$this, 'save_user_settings' ) );
        }

        public function add_live_admin_settings( $profileuser ) {
            ?>
                </tbody>
            </table>
            <h3>Live Admin</h3>
            <table class="form-table">
                <tbody>
            <?php
            do_action('live_admin_options', $profileuser);
        }

        public function add_user_settings( $profileuser ) {
            $displayed_user_settings = get_user_meta( $profileuser->ID, 'live-admin', true );
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

        /**
         * Save all Live Admin user settings at once
         */
        public function save_user_settings() {
            // Make sure this only happens once
            if ( defined('LIVE_ADMIN_SAVED' ) && LIVE_ADMIN_SAVED )
                return;

            define ( 'LIVE_ADMIN_SAVED', true );

            $user_id = get_current_user_id();
            $settings = $_POST['live_admin'];
            $options = $_POST['live_admin_options'];

            if ( ! is_array ( $options ) ) {
                return;
            }

            foreach ( $options as $option => &$value ) {
                if ( isset ( $settings[$option] ) )
                    $value = $settings[$option];
            }

            update_user_meta ( $user_id, 'live-admin', $options );
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