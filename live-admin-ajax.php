<?php
// Exit if accessed directly
if ( !defined ( 'ABSPATH' ) )
    exit;

if ( defined ( 'LIVE_ADMIN_AJAX' ) )
    return;

define ( 'LIVE_ADMIN_AJAX', true );

add_action('wp_ajax_live-admin-save-sidebar-state', array ( 'WP_LiveAdmin_Ajax', 'save_sidebar_state' ) );

class WP_LiveAdmin_Ajax
{
    public static function save_sidebar_state() {
        check_ajax_referer( 'savesidebarstate', 'savesidebarstatenonce' );

        $sidebar_state = isset( $_POST['state'] ) ? $_POST['state'] : '';
        $handle = isset( $_POST['handle'] ) ? $_POST['handle'] : '';

        /*if ( $handle != sanitize_key( $handle ) )
            wp_die( 0 );*/

        if ( ! $user = wp_get_current_user() )
            wp_die( -1 );

        if ( is_string($sidebar_state) )
            update_user_option($user->ID, "sidebarstate_$handle", $sidebar_state, true);

        wp_die ( 1 );

    }
}