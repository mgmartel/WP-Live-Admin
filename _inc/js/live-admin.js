// Customizer
jQuery(document).ready( function($) {
    // Sidebar collpase
    var body = $( document.body ),
        overlay = body.children('.wp-full-overlay');

    $('.collapse-sidebar').click( function( event ) {
        overlay.toggleClass( 'collapsed' ).toggleClass( 'expanded' );

        // Save state?
        // @TODO also remember metabox state
        if ( rememberSidebarState ) {
            $.post(ajaxurl, {
                action:                 'live-admin-save-sidebar-state',
                savesidebarstatenonce:  jQuery('#savesidebarstatenonce').val(),
                state:                  liveAdmin_getSidebarState(),
                handle:                 handle
            });
        }

        event.preventDefault();
    });

    $('.customize-section-title').click( function( event ) {
        var clicked = $( this ).parents( '.customize-section' );

        if ( clicked.hasClass('cannot-expand') )
            return;

    // Temporary accordeon
    $( '.customize-section' ).not( clicked ).removeClass( 'open' );
        clicked.toggleClass( 'open' );
        event.preventDefault();
    });

});

jQuery(document).ready(function($) {
    // iFrame
    if ( ! overrideIFrameLoader ) {

        var reqUrl = iframeUrl,
                    iframe = $('.wp-full-overlay-main iframe');

        // Fade iFrame in onload
        iframe.load(function() {
            // Make sure admin links take over the window instead of the iFrame
            iframe.contents().find('a').click( function(e) {
                if ( disableNavigation ) {
                    e.preventDefault();
                    e.stopPropagation();
                    return;
                }

                if ( e.target.href.indexOf( 'wp-admin' ) != -1  ) {
                    e.stopPropagation(); e.preventDefault();
                    window.location.href = e.target;
                }
            })

            iframe.fadeIn( function() {
                $(window).trigger('iframeLoaded');
            });
        });

        iframe.attr('src',reqUrl);

    }

    // Some general listeners
    if ( ! disableListeners ) {
        $("a.edit-current-post").click( function(e) {
            var current_editUrl = liveAdmin_getCurrentPostEditUrl();
            if ( current_editUrl ) {
                e.preventDefault();
                window.location.href = current_editUrl;
            }
        });
    }

});


// Misc functions
function liveAdmin_getCurrentPostEditUrl() {
    var iframe = jQuery('.wp-full-overlay-main iframe').contents();

    if ( iframe.find("#wp-admin-bar-edit a").get(0) )
        return iframe.find("#wp-admin-bar-edit a").first().attr("href");

    if ( iframe.find("a.post-edit-link").get(0) )
        return iframe.find("a.post-edit-link").first().attr("href");

    if ( iframe.find("span.edit-link a").get(0) )
        return iframe.find("span.edit-link a").first().attr("href");

    return null;
}

function liveAdmin_getSidebarState() {
    var overlay = jQuery( document.body ).children('.wp-full-overlay');
    if ( overlay.hasClass('expanded') )
        return 'expanded';
    else return 'collapsed';
}