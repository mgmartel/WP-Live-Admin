// Customizer
jQuery(document).ready( function($) {
    // Sidebar collpase
    var body = $( document.body ),
        overlay = body.children('.wp-full-overlay');

    $('.collapse-sidebar').click( function( e ) {
        e.preventDefault();
        toggleLiveAdminSidebar();
    });

    function toggleLiveAdminSidebar() {
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
    }

    $('.customize-section-title').click( function( event ) {
        var clicked = $( this ).parents( '.customize-section' );

        if ( clicked.hasClass('cannot-expand') )
            return;

    // Temporary accordeon
    $( '.customize-section' ).not( clicked ).removeClass( 'open' );
        clicked.toggleClass( 'open' );
        //event.preventDefault();
    });

    // Open metabox based on hash
    if ( window.location.hash ) {
        open_metabox ( window.location.hash.substring(1) );
    }
    $(window).bind('hashchange', function() {
        open_metabox ( window.location.hash.substring(1) );
        $('.wp-full-overlay-sidebar-content').scrollTop($( window.location.hash ).offset().top);
    });

    function open_metabox( id ) {
        var metabox = $('li#' + id);
        if ( metabox.get(0) ) {

            if ( metabox.hasClass('cannot-expand') )
                return;

            // Temporary accordeon
            $( '.customize-section' ).not( metabox ).removeClass( 'open' );
                metabox.toggleClass( 'open' );
        }
    }

});

jQuery(document).ready(function($) {
    // iFrame
    if ( ! overrideIFrameLoader ) {

        var reqUrl = iframeUrl,
                    iframe = $('.wp-full-overlay-main iframe');

        // Fade iFrame in onload
        iframe.load(function() {
            if ( disableNavigation ) {
                iframe.contents().find('form').on( 'submit', function(e) {
                    e.preventDefault();
                });
            }

            iframe.contents().on('click', 'a', function(e) {

                if ( disableNavigation ) {
                    e.preventDefault();
                    e.stopPropagation();
                    return;
                }

                // Force loading of external links in new window
                var openInNewWindow = window.location.hostname;
                if ( allowSameDomainLinks ) {
                    var domainParts = window.location.hostname.split(".");
                    openInNewWindow = domainParts[domainParts.length - 2] + '.' + domainParts[domainParts.length - 1];
                }

                if(this.href.indexOf(openInNewWindow) == -1) {
                    $(this).attr('target', '_blank');
                }

                // Make sure admin links take over the window instead of the iFrame
                if ( this.href.indexOf( 'wp-admin' ) != -1  ) {
                    e.stopPropagation(); e.preventDefault();
                    window.location.href = this.href;
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

function liveAdmin_addQueryParam(key, value, url) {
    if (!url) url = window.location.href;
    var re = new RegExp("([?|&])" + key + "=.*?(&|#|$)", "gi");

    if (url.match(re)) {
        if (value)
            return url.replace(re, '$1' + key + "=" + value + '$2');
        else
            return url.replace(re, '$2');
    }
    else {
        if (value) {
            var separator = url.indexOf('?') !== -1 ? '&' : '?',
                hash = url.split('#');
            url = hash[0] + separator + key + '=' + value;
            if (hash[1]) url += '#' + hash[1];
            return url;
        }
        else
            return url;
    }
}

function liveAdmin_addCurrentPageParam(link, current_page) {
    if (!current_page) current_page = jQuery('.wp-full-overlay-main iframe').contents().get(0).location.href;

    // Is it an admin url? If so, refuse!
    if ( current_page.indexOf(admin_url) !== -1 )
        return link;

    if ( current_page.indexOf(site_url) !== -1 ) {
        // Siteurl is still in there
        var site_url_length = site_url.length;
        current_page = current_page.substr( site_url_length );
    }

    link = liveAdmin_addQueryParam('current-page', encodeURIComponent(current_page), link);

    return link;
}

function liveAdmin_isExpanded() {
    return jQuery('.wp-full-overlay').hasClass('expanded');
}