jQuery(document).ready( function($) {

    // Hacky way of hiding the menu
    $("#adminmenuback, #adminmenuwrap, .wp-full-overlay-sidebar").addClass( 'collapsed' ).removeClass( 'expanded' );

    /*
     * @todo L10n
     */
    var hideMenuButton = '<li id="hide-menu" class="hide-if-no-js toggle-menu"><div id="collapse-button"><div></div></div><span>Hide menu</span></li>';
    $('#adminmenuwrap > ul').prepend(hideMenuButton);

    // Menu
    $('.toggle-menu').click( function(e) {
        toggleLiveMenu();
        e.preventDefault();
    });

    var iframe = $('.wp-full-overlay-main iframe');
    iframe.load(function() {
        // Add menu to WP icon in Admin Bar
        var adminbar_wp_logo = iframe.contents().find("#wp-admin-bar-wp-logo"),
            adminbar_wp_logo_contents = adminbar_wp_logo.html();

        // Set Adminbar toggle
        toggleAdminBarToggle ( liveAdmin_isExpanded() );
        $('.collapse-sidebar').click( function() {
            toggleAdminBarToggle ( liveAdmin_isExpanded() );
        });

        function toggleAdminBarToggle( hasClassExpanded ) {
            if ( hasClassExpanded )
                adminbar_wp_logo.html( adminbar_wp_logo_contents );
            else {
                adminbar_wp_logo.html("<span style='width:" + adminbar_wp_logo.width() + "px;text-align:center;cursor:pointer;display:inline-block;' class='toggle-menu'>&#9776;</span>");
                adminbar_wp_logo.find("span.toggle-menu").click( function(e) {
                    toggleLiveMenu();
                    e.preventDefault();
                });
            }
        }
    });


    $.hotkeys.add('m', { disableInInput: true, propagate: true }, function() {
            toggleLiveMenu();
    });

    function toggleLiveMenu() {
        $('.wp-full-overlay-sidebar').toggleClass( 'collapsed' ).toggleClass( 'expanded' );
        $("#adminmenuback, #adminmenuwrap").toggleClass( 'collapsed' ).toggleClass( 'expanded' );
    }
});