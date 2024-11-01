/*
Enqueued values from checklist_in_post.php

options.cookies
*/

(function ($) {
    "use strict";

    $(document).ready(function () {

        // Add note
        $( document.body ).on( 'click', '#wp-admin-bar-postit-add a', function( e ) {

            var data = { action: 'postit_add', nonce: postitnonce.nonce };

            $.post( ajaxurl, data, function( response ) {
                response = jQuery.parseJSON( response );
                jQuery( '#postbox-container-1 #normal-sortables' ).prepend( response.postit );
                jQuery('body, html').animate({ scrollTop: $( "#wpf-post-it-" + response.post_id ).offset().top - 50 }, 750); // scroll down
                jQuery( '#wpf-post-it-' + response.post_id + ' .modify-post-it' ).focus();
            });

            // Stop scrollTop animation on user scroll
            $( 'html, body' ).bind("scroll mousedown DOMMouseScroll mousewheel keyup", function( e ){
                if ( e.which > 0 || e.type === "mousedown" || e.type === "mousewheel") {
                    $( 'html, body' ).stop().unbind('scroll mousedown DOMMouseScroll mousewheel keyup');
                }
            });

            e.preventDefault();

        });

        //Edit .checklist-list ul appearance
        $('.checklist_in_post > ul').wrap("<form class='checklist-list'>");

        var $id = 1;
        $('.checklist_in_post > form.checklist-list > ul > li').each(function () {
            var $text = $(this).html();
            $(this).html(' ');
            $(this).prepend("<span class='checklist-wrap'><input type=checkbox id='" + $id + "' /><label for='" + $id + "' class='checklist-label'><i class='fa fa-check'></i>" + $text + "</label></span>");
            $id = $id + 1;
        });


        var $checkedli = $('.checklist_in_post li');
        $checkedli.on('change', function (e) {
            $(this).toggleClass('checklist-checked');
            $(this).find('.checklist-label').toggleClass('checklist-label-checked');
            var index = $checkedli.index($(this));
            toggleCookie('checkedLi[' + index + ']');
        });

        $('div[id^=wpf-post-it]').on('click', '.add-new-comment', function (action) {
            var postid = $(this).data('postid');
            var $toggleButton = $(this),
                $form = $('.new-comment-form-' + postid ),
                $target = $();

            $toggleButton.attr('aria-expanded', 'true');
            $form.show();
        });

        $('div[id^=wpf-post-it]').on('click', '.new-comment-cancel', function (action) {
            var postid = $(this).data('postid'),
                $cancelButton = $(this),
                $form = $('.new-comment-form-' + postid );

            $cancelButton.attr('aria-expanded', 'false');
            $form.hide();
        });

        $('div[id^=wpf-post-it]').on('submit', '.comment-form-postit', function (event) {
            var text = $(this).find('#new-comment-text').val();
            var postid = $(this).find('#postid').val();
            var nonce = $(this).find('#noncepostit_' + postid).val();
            var referer = $(this).find('input[name="_wp_http_referer"]').val();

            event.preventDefault();

            /*
             * Don't trigger a search if the search field is empty or the
             * search term was made of only spaces before being trimmed.
             */
            if (!text) {
                return;
            }

            var $spinner = $('.new-comment-form-' + postid).children('.spinner');

            $spinner.addClass('is-active');

            var requestParams = {};
            requestParams["noncepostit_" + postid] = nonce;
            requestParams.postid = postid;
            requestParams.text = text;
            requestParams.referer = referer;

            wp.ajax.post('postitaddnewcomment', requestParams)
                .always(function () {
                    $spinner.removeClass('is-active');
                })

                .done(function (response) {
                    var params = {};
                    params.post_id = response.post_id;
                    params._wpnonce = response.nonce;
                    wp.ajax.post('postitlistcomment', params).done(function (response) {
                        var parent = $('#wpf-post-it-' + response.post_id);
                        parent.find('#comments').html( response.html );
                        $('.new-comment-form-' + postid).find('#new-comment-text').val('');
                        $('.new-comment-form-' + postid).hide();
                        parent.find('.add-new-comment').attr( 'aria-expanded', false );
                    });
                })
        });

        //Cookies
        //Check cookie option
        if (options.cookies) {
            //Default cookies or existing one for li's:
            $checkedli.each(function (index) {
                if (checkCookie('checkedLi[' + index + ']')) {
                    $(this).toggleClass('todo-checked');
                    $(this).find('.checklist-label').toggleClass('checklist-label-checked');
                }
            });
        }

        /**
         * Function for getting path of current site
         * @returns {string}
         */
        function getAbsolutePath() {
            var loc = window.location;
            var pathName = loc.pathname.substring(0, loc.pathname.lastIndexOf('/') + 1);
            //return loc.href.substring(0, loc.href.length - ((loc.pathname + loc.search + loc.hash).length - pathName.length));
            return pathName;
        }

        /**
         * Cookie create function
         *
         * @param cname Cookie Name
         * @param cvalue Cookie Value
         * @param exdays Expires Date
         */
        function setCookie(cname, cvalue, exdays) {
            var d = new Date();
            d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
            var expires = "expires=" + d.toUTCString();
            var pathName = getAbsolutePath();
            console.log(pathName);
            document.cookie = cname + "=" + cvalue + ";" + expires + ";path=" + pathName;
        }

        /**
         * Cookie get function
         * @param cname
         * @returns {*}
         */
        function getCookie(cname) {
            var name = cname + "=";
            var ca = document.cookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) == ' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(name) == 0) {
                    return c.substring(name.length, c.length);
                }
            }
            return "";
        }

        /**
         * Function to check if cookie is 1 or 0
         * @param cname
         */
        function toggleCookie(cname) {
            console.log('toggleCookie ' + cname);
            var cookie = getCookie(cname);
            if (cookie === "1") {
                //Change to 0
                setCookie(cname, 0, 0);

            } else {
                setCookie(cname, 1, 365);
            }
        }

        /**
         * Function to check cookie fast
         * @param cname
         * @returns {boolean}
         */
        function checkCookie(cname) {
            console.log('checkCookie ' + cname);
            var cookie = getCookie(cname);
            if (cookie === "1") {
                return true;
            }
            return false;
        }


    });

})(jQuery);