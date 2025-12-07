jQuery(function($){
    $(document).ready(function(){
        let siotpAdminHandler = {
                loginForm: $('#loginform'),
                customUploader: '',
                init: function () {
                    this.initiate();
                    this.helpTip();
                    $( 'body' ).on( 'click', { siotpAdminHandler: this }, this.hideColorPicker );
                    $( document )
                        .on( 'click', '#signin_with_otp_text', { siotpAdminHandler: this }, this.checkLoginForm )
                        .on( 'click', '#siotp_email_header_image_upload', { siotpAdminHandler: this }, this.setEmailHeaderImage )
                        .on( 'click', '#signin_with_otp_back_button', { siotpAdminHandler: this }, this.backToLoginForm )
                        .on( 'click', '#signin_with_otp_login_button', { siotpAdminHandler: this }, this.authenticate )
                        .on( 'keyup', '#signin_with_otp', { siotpAdminHandler: this }, this.allowOnlyDigits )
                        .on( 'click', '#siotp_login_icon_upload', { siotpAdminHandler: this }, this.logoUploader )
                        .on( 'focus click', '#siotp-login-wrap #login input', { siotpAdminHandler: this }, this.checkInput )
                        .on( 'focusout', '#siotp-login-wrap #login input', { siotpAdminHandler: this }, this.onKeyUp ) 
                },
                block: function( divID ) {
                    $( divID ).addClass( 'si_processing' );
                },
                unblock: function( divID ) {
                    setTimeout( function(){ $( divID ).removeClass( 'si_processing' ); }, 500 );
                },
                initiate: function () {
                    this.beautifyLoginForm();
                    this.checkInput();

                    let siotpHtml = captchaHtml ='';
                    siotpHtml += '<div class="signin_with_otp_text_wrap">';
                    siotpHtml +=    '<div class="line"></div>';
                    siotpHtml +=    '<a href="javascript:;" id="signin_with_otp_text" data-type="loginText"><i class="siotp-icon"></i><span>' + signInWithOtp.signinText + '</span></a>';  
                    siotpHtml += '</div>';
                    this.loginForm.after( siotpHtml );  

                    if( 'yes' == signInWithOtp.greCaptcha.enabled ) {
                        captchaHtml += '<div id="signin_with_otp_captcha_wrap">';
                        captchaHtml +=      '<form id="siotpCaptchaForm" method="post">';
                        captchaHtml +=          '<h3>' + signInWithOtp.captchaText + '</h3>'; 
                        captchaHtml +=          '<a data-step="second" id="signin_with_otp_back_button">' + signInWithOtp.backText + '</a>';  
                        captchaHtml +=          '<div class="captcha_wrap">';                             
                        captchaHtml +=              '<div id="html_element"></div>';                             
                        captchaHtml +=           '</div>';  
                        captchaHtml +=          '<input type="hidden" id="signin_with_otp_user" value="" />';  
                        captchaHtml +=      '</form>';
                        captchaHtml += '</div>';  
                        this.loginForm.after( captchaHtml );
                    }                     

                    if( this.loginForm.length <= 0 ) {
                        // Color picker
                        $( '.colorpick' )
                            .iris({
                                change: function( event, ui ) {
                                    $( this ).parent().find( '.colorpickpreview' ).css({ backgroundColor: ui.color.toString() });
                                },
                                hide: true,
                                border: true
                            })

                            .on( 'click focus', function( event ) {
                                event.stopPropagation();
                                $( '.iris-picker' ).hide();
                                $( this ).closest( 'td' ).find( '.iris-picker' ).show();
                                $( this ).data( 'original-value', $( this ).val() );
                            })

                            .on('change', function() {
                                if ( $( this ).is( '.iris-error' ) ) {
                                    var originalValue = $( this ).data( 'original-value' );

                                    if ( originalValue.match( /^\#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/ ) ) {
                                        $( this ).val( $( this ).data( 'original-value' ) ).change();
                                    } else {
                                        $( this ).val('').change();
                                    }
                                }
                            });
                    }
                    $( 'p#nav, p#backtoblog' ).wrapAll( '<div class="signin_with_otp_nav_wrap" />' );
                },
                helpTip: function() {
                    if( 'yes' === signInWithOtp.isAdminSettings ) {
                        $( '.jxn-help-tip' ).tipTip({
                            'attribute': 'data-tip',
                            'fadeIn':    50,
                            'fadeOut':   50,
                            'delay':     200
                        });
                    }
                },
                checkLoginForm: function(e) {
                    if( siotpAdminHandler.loginForm.length > 0 ) {

                        let userName = $( '#user_login' ).val(),
                            dataType = $( this ).data( 'type' );
                        if( ! userName ) {
                            if( $( '#login_error' ).length <= 0 ) {
                                siotpAdminHandler.loginForm.before( '<div id="login_error"><strong>Error</strong>: The username field is empty.</div>' );
                            }
                        } else {
                            $( '#login_error, .success' ).remove();
                            $( '#signin_with_otp_user' ).val( userName );
                            siotpAdminHandler.checkUserExistence( dataType );
                        }
                    }                
                },
                checkUserExistence: function( dataType ) {
                    let userName = $( '#user_login' ).val();               

                    if( userName.length > 3 ) {
                        siotpAdminHandler.block( '#loginform' );
                        $.ajax({
                            url: signInWithOtp.ajaxUrl,
                            data: {
                                'action': 'check_user_existence',
                                'log' : userName,
                            },
                            type: 'post',
                            dataType: 'json',
                            success: function( data, textStatus, XMLHttpRequest ) {
                                if( data.status == 'success' ) {
                                    $( '#login_error, .success, .message' ).remove();                
                                    $( '.signin_with_otp_text_wrap, #signin_with_otp_wrap' ).hide();
                                    siotpAdminHandler.loginForm.hide();
                                    if( 'yes' == signInWithOtp.greCaptcha.enabled ) {
                                        $( '#signin_with_otp_captcha_wrap' ).show();
                                        grecaptcha.reset();
                                    } else {
                                        siotpAdminHandler.otpHandler(userName);
                                    }
                                } else {
                                    $( '#login_error, .success, .message' ).remove();
                                    $( '#signin_with_otp_captcha_wrap' ).hide();
                                    $( '.signin_with_otp_text_wrap' ).show();
                                    siotpAdminHandler.loginForm.before( data.messageHtml ).show();
                                    if( dataType == 'regenerateText' ) {
                                        siotpAdminHandler.loginForm.hide();
                                    }
                                }
                                siotpAdminHandler.unblock( '#loginform' );
                            },
                            error: function( MLHttpRequest, textStatus, errorThrown ) {
                                console.log( textStatus );
                            }
                        });
                    }
                },
                otpHandler:function( user ) {
                    let otpHtml ='';
                    $( '#login_error, .success, .message' ).remove(); 
                    $.ajax({
                        url: signInWithOtp.ajaxUrl,
                        data: {
                            'action': 'generate_email_otp',
                            'user' : user,
                        },
                        type: 'post',
                        dataType: 'json',
                        success: function( data, textStatus, XMLHttpRequest ) {
                            if( data.status == 'success' ) {
                                otpHtml += '<div id="signin_with_otp_wrap"><form id="siotpOtpForm" method="post">';
                                otpHtml += '<h3>' + signInWithOtp.otpText + '</h3>';
                                otpHtml += '<a data-step="third" id="signin_with_otp_back_button">' + signInWithOtp.backText + '</a>';  
                                otpHtml += '<p class="countdown"><span></span></p>';                             
                                otpHtml += '<div class="otp_wrap">';                             
                                otpHtml += '<label>' + signInWithOtp.otpText + '</label>';                             
                                otpHtml += '<input type="text" name="signin_with_otp" id="signin_with_otp" value="" minlength="6" maxlength="6" />';                             
                                otpHtml += '</div>';  
                                otpHtml += '<input type="button" class="button button-primary button-large" id="signin_with_otp_login_button" value="Log In" />';  
                                otpHtml += '<input type="hidden" id="signin_with_otp_user" value="' + user + '" />';  
                                otpHtml += '</form></div>';  
                                $( '#loginform' ).after( otpHtml ); 
                                $('.signin_with_otp_text_wrap').html( '<div class="line"></div><p>' + signInWithOtp.notReceivedText + '</p><a href="javascript:;" id="signin_with_otp_text" data-type="regenerateText">' + signInWithOtp.regenerateText + '</a>' ).show();          
                            } else {
                                setTimeout( function(){ $( data.messageHtml ).insertAfter( $( '#loginform' ) ); }, 300 );
                                $( '#login_error, .success, .message' ).remove();
                            }
                        },
                        error: function( MLHttpRequest, textStatus, errorThrown ) {
                            console.log( textStatus );
                        }
                    });
                },
                hideColorPicker: function() {
                    $( '.iris-picker' ).hide();
                },
                setEmailHeaderImage: function(e) {
                    e.preventDefault();
                    let mediaUploader;

                    if ( mediaUploader ) {
                        mediaUploader.open();
                        return;
                    }
                    mediaUploader = wp.media.frames.file_frame = wp.media({
                        title: 'Choose Header Image',
                        button: {
                            text: 'Choose Header Image'
                        }, 
                        multiple: false,
                        library: {
                            type: [ 'image' ]
                        },
                    });
                    mediaUploader.on('select', function() {
                        var attachment = mediaUploader.state().get( 'selection' ).first().toJSON();
                        $( '#siotp_email_header_image' ).val( attachment.url );
                    });
                    mediaUploader.open();
                },
                backToLoginForm: function(){ 
                    if( $( this ).data( 'step' ) == 'second' ){
                        $( '#login_error, .success, .message' ).remove();
                        $( '#signin_with_otp_captcha_wrap' ).hide();
                        siotpAdminHandler.loginForm.show();
                        $( '.signin_with_otp_text_wrap' ).show();
                    } else {
                        if( signInWithOtp.greCaptcha.enabled == 'yes' ) {
                            grecaptcha.reset();
                        }
                        $( '#login_error, .success, .message' ).remove();
                        $( '.signin_with_otp_text_wrap, #signin_with_otp_wrap' ).hide();
                        siotpAdminHandler.loginForm.show();
                        $( '#signin_with_otp_captcha_wrap' ).show();
                        $( '.signin_with_otp_text_wrap' ).html( '<a href="javascript:;" id="signin_with_otp_text" data-type="loginText">' + signInWithOtp.signinText + '</a>' ).show();  
                    }
                },
                authenticate: function() {
                    let userName = $( '#signin_with_otp_user' ).val(),
                        otpPass = $( '#signin_with_otp' ).val();

                    if( !otpPass ) {
                        $( '#login_error, .success' ).remove();
                        if( $( '#login_error' ).length <= 0 ) {
                            siotpAdminHandler.loginForm.before( '<div id="login_error"><strong>Error</strong>: The OTP field is empty.</div>' );
                        }
                    } else if(otpPass.length < 5) {
                        $( '#login_error, .success' ).remove();
                        if( $( '#login_error' ).length <= 0 ) {
                            siotpAdminHandler.loginForm.before( '<div id="login_error"><strong>Error</strong>: The OTP entered is invalid. Please enter correct OTP.</div>' );
                        }    
                    } else {                        
                        $( '#login_error, .success' ).remove();
                        $.ajax({
                            url: signInWithOtp.ajaxUrl,
                            data: {
                                'action': 'authenticate_user',
                                'username' : userName,
                                'otp_pass' : otpPass
                            },
                            type: 'post',
                            dataType: 'json',
                            success: function( data, textStatus, XMLHttpRequest ) {
                                if( data.status == 'success' ) {
                                    window.location.href = data.adminUrl;
                                } else {
                                    $( '#login_error, .success, .message' ).remove();
                                    $( '#signin_with_otp_captcha_wrap' ).hide();
                                    $( '.signin_with_otp_text_wrap' ).show();
                                    siotpAdminHandler.loginForm.before(data.messageHtml); 

                                    if( data.user_locked ) {
                                        $( '.countdown' ).html('').hide();
                                        $.ajax({
                                            url: signInWithOtp.ajaxUrl,
                                            data: {
                                                'action': 'call_unlock_user_scheduler',
                                                'user': userName
                                            },
                                            type: 'post',
                                            dataType: 'json',
                                            success: function( data, textStatus, XMLHttpRequest ) {

                                            },
                                            error: function( MLHttpRequest, textStatus, errorThrown ) {
                                                console.log( textStatus );
                                            }
                                        });
                                    }                                   
                                }
                                siotpAdminHandler.unblock( '#loginform' );
                            },
                            error: function(MLHttpRequest, textStatus, errorThrown) {
                                console.log( textStatus );
                            }
                        });
                    }    
                }, 
                allowOnlyDigits: function() {
                    this.value = this.value.replace(/[^0-9\+]/g,'');
                },
                beautifyLoginForm: function() {
                    let loginWrap  = $( '#login' );
                    let rememberMe = $( '.login form .forgetmenot input[type="checkbox"]' );

                    if( loginWrap.length > 0 ) {

                        var img = $( '<img>' ).attr({
                            "src": signInWithOtp.siteLogo,
                            "alt": signInWithOtp.siteName,
                            "width": "250"
                        });
                        loginWrap.wrap( 
                            $( '<div>', {
                                id: 'siotp-login-wrap',
                            })
                        ).wrap( 
                            $( '<div>', {
                                id: 'siotp-login-wrapper',
                            })
                        ).before(
                            $( '<div>', {
                                class: 'siotp-login-icon',
                            })
                        );

                        $( '<div>', {
                            class: 'siotp-site-icon',
                        })
                        .appendTo( '.siotp-login-icon' )
                        .html( img )

                        $( '<h3>', {
                            class: 'siotp-site-name',
                        })
                        .appendTo( '.siotp-login-icon' )
                        .html( signInWithOtp.siteName );

                        rememberMe.wrap( 
                            $( '<label>', {
                                class: 'siotp-checkbox',
                            })
                            .html( 'Remember Me' )
                        )
                        .addClass( 'checkbox__input' )
                        .after(
                            $( '<span>', {
                                class: 'checkbox__label',
                            })
                        );
                    }
                },
                logoUploader: function(e) {
                    e.preventDefault();
                    if ( siotpAdminHandler.customUploader) {
                        siotpAdminHandler.customUploader.open();
                        return;
                    }

                    siotpAdminHandler.customUploader = wp.media.frames.file_frame = wp.media({
                        title: 'Upload Logo',
                        button: {
                            text: 'Choose Logo'
                        },
                        multiple: false,
                        library: {
                            type: [ 'image' ]
                        },
                    });

                    siotpAdminHandler.customUploader.on('select', function() {                    
                        attachment = siotpAdminHandler.customUploader.state().get('selection').first().toJSON();
                        $( '#siotp_login_icon' ).val( attachment.url );
                    });
                    siotpAdminHandler.customUploader.open();
                },
                checkInput: function(e) {                    
                    if( undefined === e ) {
                        $( "#login form" ).each(function(){
                            var formInputs = $( this ).find( ':input' );
                            $( formInputs ).each(function(){
                                var formInput = $( this );
                                if( "text" === formInput[0].type || "password" === formInput[0].type ) {
                                    var inputId = '#' + formInput[0].id;
                                    if( 'password' == formInput[0].type ) {
                                        $( inputId ).parent().parent().addClass( 'active' );
                                    } else {
                                        $( inputId ).parent().addClass( 'active' );
                                    }
                                }
                            });    
                        }); 
                    } else {
                        if ( $( this ).val().length <= 0 ) {
                            if( 'password' == e.target.type ) {
                                $( this ).parent().parent().addClass( 'active' );
                            } else {
                                $( this ).parent().addClass( 'active' );
                            }
                        }
                    }
                },
                onKeyUp: function(e) {
                    if ( $( this ).val().length <= 0 ) {
                        if( 'password' == e.target.type ) {
                            $( this ).parent().parent().removeClass( 'active' );
                        } else {
                            $( this ).parent().removeClass( 'active' );
                        }
                    }
                }
            };
            siotpAdminHandler.init();
    }); 
}); 
var onloadCallback = function() {
    grecaptcha.render( 'html_element', {
        'sitekey' : signInWithOtp.greCaptcha.siteKey,
        'callback' : verifyCallback,
    });
};


var verifyCallback = async function(response) {
    if( response.length > 0 ) {
        clearInterval( siotpInterval );
        jQuery( '#signin_with_otp_captcha_wrap' ).addClass( 'si_processing' );        
        jQuery.ajax({
            url: signInWithOtp.ajaxUrl,
            data: {
                'action': 'generate_email_otp',
                'user' : jQuery( '#signin_with_otp_user' ).val(),
            },
            type: 'post',
            dataType: 'json',
            success: function( data, textStatus, XMLHttpRequest ) {
                if( data.status == 'success' ) {
                    let otpHtml ='';
                    jQuery( '#login_error, .success, .message' ).remove();  
                    jQuery( '#loginform' ).before( data.messageHtml );
                    otpHtml += '<div id="signin_with_otp_wrap"><form id="siotpOtpForm" method="post">';
                    otpHtml += '<h3>' + signInWithOtp.otpText + '</h3>';
                    otpHtml += '<a data-step="third" id="signin_with_otp_back_button">' + signInWithOtp.backText + '</a>';  
                    otpHtml += '<p class="countdown"><span></span></p>';                             
                    otpHtml += '<div class="otp_wrap">';                             
                    otpHtml += '<label>' + signInWithOtp.otpText + '</label>';                             
                    otpHtml += '<input type="text" name="signin_with_otp" id="signin_with_otp" value="" minlength="6" maxlength="6" />';                             
                    otpHtml += '</div>';  
                    otpHtml += '<input type="button" class="button button-primary button-large" id="signin_with_otp_login_button" value="Log In" />';  
                    otpHtml += '</form></div>';  
                    jQuery( '#loginform' ).after( otpHtml ); 
                    jQuery( '#signin_with_otp_captcha_wrap' ).hide();    
                    setTimerInterval();
                      
                    jQuery('.signin_with_otp_text_wrap').html( '<div class="line"></div><p>' + signInWithOtp.notReceivedText + '</p><a href="javascript:;" id="signin_with_otp_text" data-type="regenerateText">' + signInWithOtp.regenerateText + '</a>' ).show();          
                } else {
                    setTimeout( function(){ jQuery( data.messageHtml ).insertAfter( jQuery( '#loginform' ) ); }, 300 );
                    jQuery( '#login_error, .success, .message' ).remove();
                }
                setTimeout( function(){ jQuery( '#signin_with_otp_captcha_wrap' ).removeClass( 'si_processing' ); }, 500 );
            },
            error: function( MLHttpRequest, textStatus, errorThrown ) {
                console.log( textStatus );
            }
        });
    }
};

let siotpInterval;

function setTimerInterval(){
    let otpValidityTime = signInWithOtp.otpValidityTime + ":00"
    siotpInterval = setInterval(function() {
        var timer = otpValidityTime.split(':'),
            totalSeconds = parseInt( timer[0], 10 ) * 60 + parseInt( timer[1], 10 ),
            minutes = Math.floor( totalSeconds / 60 ),
            seconds = totalSeconds - ( minutes * 60 );

        --seconds;
        minutes = (seconds < 0) ? --minutes : minutes;

        if ( minutes < 0 ) 
            clearInterval( siotpInterval );

        seconds      = ( seconds < 0 ) ? 59 : seconds;
        seconds      = ( seconds < 10 ) ? '0' + seconds : seconds;
        minutesHtml  = ( minutes < 10 ) ? '0' + minutes : minutes;
        var timeHtml = '<span class="signin_with_otp_minutes">' + minutesHtml + '</span><small>:</small><span class="signin_with_otp_seconds">' + seconds + '</span>';
        jQuery('.countdown span').html( timeHtml );
        otpValidityTime = minutes + ':' + seconds;

        if( otpValidityTime < '0:00' ) {
            jQuery( '.countdown span' ).html( '<span class="signin_with_otp_minutes">00</span><small>:</small><span class="signin_with_otp_seconds">00</span>' );
            jQuery.ajax({
                url: signInWithOtp.ajaxUrl,
                data: {
                    'action': 'disable_otp_after_validity',
                    'user' : jQuery( '#signin_with_otp_user' ).val(),
                },
                type: 'post',
                dataType: 'json',
                success: function(data, textStatus, XMLHttpRequest) {
                    jQuery( '#login_error, .success, .message' ).remove();
                    jQuery( '#loginform').before( data.messageHtml );                                   
                },
                error: function( MLHttpRequest, textStatus, errorThrown ) {
                    console.log( textStatus );
                }  
            }); 
        }     
    }, 1000); 
}