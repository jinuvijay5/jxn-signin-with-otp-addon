jQuery(function($) {
    $(document).ready(function() {

        const SIOTP = {
            loginForm: $('#loginform'),
            customUploader: null,
            interval: null,

            init() {
                this.bindEvents();
                this.setupUI();
                this.initColorPicker();
                this.initTooltips();
            },

            bindEvents() {
                $('body').on('click', this.hideColorPicker);

                $(document)
                    .on('click', '#signin_with_otp_text', this.handleOtpClick)
                    .on('click', '#siotp_email_header_image_upload', this.uploadEmailHeaderImage)
                    .on('click', '#signin_with_otp_back_button', this.handleBackToLogin)
                    .on('click', '#signin_with_otp_login_button', this.authenticate)
                    .on('keyup', '#signin_with_otp', this.restrictToDigits)
                    .on('click', '#siotp_login_icon_upload', this.uploadLogo)
                    .on('focus click', '#siotp-login-wrap #login input:not([type="checkbox"]), #signin_with_otp', this.handleInputFocus)
                    .on('focusout', '#siotp-login-wrap #login input:not([type="checkbox"]), #signin_with_otp', this.handleInputBlur);
            },

            setupUI() {
                this.decorateLoginForm();
                this.createOtpTriggerLink();
                this.createCaptchaHtml();
                $('p#nav, p#backtoblog').wrapAll('<div class="signin_with_otp_nav_wrap" />');
            },

            decorateLoginForm() {
                const loginWrap = $('#login');
                const rememberMe = $('.login form .forgetmenot input[type="checkbox"]');

                if (loginWrap.length) {
                    const logoImg = $('<img>', {
                        src: signInWithOtp.siteLogo,
                        alt: signInWithOtp.siteName,
                        width: 250
                    });

                    loginWrap.wrap('<div id="siotp-login-wrap"></div>')
                        .wrap('<div id="siotp-login-wrapper"></div>')
                        .before('<div class="siotp-login-icon"></div>');

                    $('.siotp-login-icon')
                        .append($('<div class="siotp-site-icon">').html(logoImg))
                        .append($('<h3 class="siotp-site-name">').text(signInWithOtp.siteName));

                    rememberMe.wrap('<label class="siotp-checkbox">Remember Me</label>')
                        .addClass('checkbox__input')
                        .after('<span class="checkbox__label"></span>');
                }
            },

            createOtpTriggerLink() {
                const html = `
                    <div class="signin_with_otp_text_wrap">
                        <div class="line"></div>
                        <a href="javascript:;" id="signin_with_otp_text" data-type="loginText">
                            <i class="siotp-icon"></i><span>${signInWithOtp.signinText}</span>
                        </a>
                    </div>
                `;
                this.loginForm.after(html);
            },

            createCaptchaHtml() {
                if (signInWithOtp.greCaptcha.enabled === 'yes') {
                    const html = `
                        <div id="signin_with_otp_captcha_wrap">
                            <form id="siotpCaptchaForm" method="post">
                                <h3>${signInWithOtp.captchaText}</h3>
                                <a data-step="second" id="signin_with_otp_back_button">${signInWithOtp.backText}</a>
                                <div class="captcha_wrap"><div id="html_element"></div></div>
                                <input type="hidden" id="signin_with_otp_user" value="" />
                            </form>
                        </div>
                    `;
                    this.loginForm.after(html);
                }
            },

            initColorPicker() {
                if (this.loginForm.length > 0) return;
                $('.colorpick').iris({
                    change(event, ui) {
                        $(this).parent().find('.colorpickpreview').css({ backgroundColor: ui.color.toString() });
                    },
                    hide: true,
                    border: true
                }).on('click focus', function(event) {
                    event.stopPropagation();
                    $('.iris-picker').hide();
                    $(this).closest('td').find('.iris-picker').show();
                    $(this).data('original-value', $(this).val());
                }).on('change', function() {
                    if ($(this).hasClass('iris-error')) {
                        const original = $(this).data('original-value');
                        const isValid = /^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/.test(original);
                        $(this).val(isValid ? original : '').change();
                    }
                });
            },

            initTooltips() {
                if (signInWithOtp.isAdminSettings === 'yes') {
                    $('.jxn-help-tip').tipTip({
                        attribute: 'data-tip',
                        fadeIn: 50,
                        fadeOut: 50,
                        delay: 200
                    });
                }
            },

            hideColorPicker() {
                $('.iris-picker').hide();
            },

            handleOtpClick(e) {
                const user = $('#user_login').val();
                const type = $(this).data('type');

                if (!user) {
                    if (!$('#login_error').length) {
                        SIOTP.loginForm.before('<div id="login_error"><strong>Error</strong>: The username field is empty.</div>');
                    }
                    return;
                }

                $('#login_error, .success').remove();
                $('#signin_with_otp_user').val(user);
                SIOTP.verifyUser(user, type);
            },

            verifyUser(username, type) {
                if (username.length <= 3) return;

                SIOTP.toggleProcessing('#loginform', true);

                $.post(signInWithOtp.ajaxUrl, {
                    action: 'check_user_existence',
                    log: username
                }, function(response) {
                    $('#login_error, .success, .message').remove();

                    if (response.status === 'success') {
                        $('.signin_with_otp_text_wrap, #signin_with_otp_wrap').hide();
                        SIOTP.loginForm.hide();

                        if (signInWithOtp.greCaptcha.enabled === 'yes') {
                            $('#signin_with_otp_captcha_wrap').show();
                            grecaptcha.reset();
                        } else {
                            SIOTP.sendOtp(username);
                        }
                    } else {
                        $('#signin_with_otp_captcha_wrap').hide();
                        $('.signin_with_otp_text_wrap').show();
                        SIOTP.loginForm.before(response.messageHtml).show();

                        if (type === 'regenerateText') {
                            SIOTP.loginForm.hide();
                        }
                    }

                    SIOTP.toggleProcessing('#loginform', false);
                }, 'json');
            },

            sendOtp(user) {
                $('#login_error, .success, .message').remove();
                const stepLabel = (signInWithOtp.greCaptcha.enabled === 'yes') ? 'third' : 'second';

                $.post(signInWithOtp.ajaxUrl, {
                    action: 'generate_email_otp',
                    user: user
                }, function(response) {
                    if (response.status === 'success') {
                        const otpHtml = `
                            <div id="signin_with_otp_wrap">
                                <form id="siotpOtpForm" method="post">
                                    <h3>${signInWithOtp.otpText}</h3>
                                    <a data-step="${stepLabel}" id="signin_with_otp_back_button">${signInWithOtp.backText}</a>
                                    <p class="countdown"><span></span></p>
                                    <div class="otp_wrap">
                                        <label>${signInWithOtp.otpText}</label>
                                        <input type="text" name="signin_with_otp" id="signin_with_otp" minlength="6" maxlength="6" />
                                    </div>
                                    <input type="button" class="button button-primary button-large" id="signin_with_otp_login_button" value="Log In" />
                                    <input type="hidden" id="signin_with_otp_user" value="${user}" />
                                </form>
                            </div>
                        `;
                        $('#loginform').after(otpHtml);
                        $('.signin_with_otp_text_wrap').html(`
                            <div class="line"></div>
                            <p>${signInWithOtp.notReceivedText}</p>
                            <a href="javascript:;" id="signin_with_otp_text" data-type="regenerateText">${signInWithOtp.regenerateText}</a>
                        `).show();
                        SIOTP.startTimer();
                        $('#signin_with_otp_captcha_wrap').hide();
                    } else {
                        $('#loginform').after(response.messageHtml);
                    }
                }, 'json');
            },

            handleBackToLogin() {
                const step = $(this).data('step'); console.log();

                $('#login_error, .success, .message').remove();

                if (step === 'second') {
                    $('#signin_with_otp_captcha_wrap, #signin_with_otp_wrap').hide();
                    SIOTP.loginForm.show();
                    $('.signin_with_otp_text_wrap').show();
                } else {
                    if (signInWithOtp.greCaptcha.enabled === 'yes') {
                        grecaptcha.reset();
                    }
                    $('.signin_with_otp_text_wrap, #signin_with_otp_wrap').hide();
                    SIOTP.loginForm.hide();
                    $('#signin_with_otp_captcha_wrap').show();
                }
            },

            authenticate() {
                const user = $('#signin_with_otp_user').val();
                const otp = $('#signin_with_otp').val();

                $('#login_error, .success').remove();

                if (!otp) {
                    SIOTP.loginForm.before('<div id="login_error"><strong>Error</strong>: The OTP field is empty.</div>');
                    return;
                }

                if (otp.length < 5) {
                    SIOTP.loginForm.before('<div id="login_error"><strong>Error</strong>: The OTP entered is invalid. Please enter correct OTP.</div>');
                    return;
                }

                $.post(signInWithOtp.ajaxUrl, {
                    action: 'authenticate_user',
                    username: user,
                    otp_pass: otp
                }, function(response) {
                    if (response.status === 'success') {
                        window.location.href = response.adminUrl;
                    } else {
                        $('#signin_with_otp_captcha_wrap').hide();
                        $('.signin_with_otp_text_wrap').show();
                        SIOTP.loginForm.before(response.messageHtml);

                        if (response.user_locked) {
                            $('.countdown').html('').hide();
                            $.post(signInWithOtp.ajaxUrl, {
                                action: 'call_unlock_user_scheduler',
                                user: user
                            });
                        }
                    }
                    SIOTP.toggleProcessing('#loginform', false);
                }, 'json');
            },

            uploadEmailHeaderImage(e) {
                e.preventDefault();

                const mediaUploader = wp.media({
                    title: 'Choose Header Image',
                    button: { text: 'Choose Header Image' },
                    multiple: false,
                    library: { type: ['image'] }
                });

                mediaUploader.on('select', function() {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#siotp_email_header_image').val(attachment.url);
                });

                mediaUploader.open();
            },

            uploadLogo(e) {
                e.preventDefault();

                if (SIOTP.customUploader) {
                    SIOTP.customUploader.open();
                    return;
                }

                SIOTP.customUploader = wp.media({
                    title: 'Upload Logo',
                    button: { text: 'Choose Logo' },
                    multiple: false,
                    library: { type: ['image'] }
                });

                SIOTP.customUploader.on('select', function() {
                    const attachment = SIOTP.customUploader.state().get('selection').first().toJSON();
                    $('#siotp_login_icon').val(attachment.url);
                });

                SIOTP.customUploader.open();
            },

            restrictToDigits() {
                this.value = this.value.replace(/[^0-9]/g, '');
            },

            handleInputFocus() {
                $(this).closest('p, div.user-pass-wrap, div.otp_wrap').addClass('active');
            },

            handleInputBlur() {
                if (!$(this).val()) {
                    $(this).closest('p, div.user-pass-wrap, div.otp_wrap').removeClass('active');
                }
            },

            toggleProcessing(selector, isLoading) {
                $(selector).toggleClass('si_processing', isLoading);
            },

            startTimer() {
                // Clear any existing timer before starting a new one
                if (SIOTP.interval) {
                    clearInterval(SIOTP.interval);
                    SIOTP.interval = null;
                }

                let minutes = 0, seconds = 0;
                if (signInWithOtp.otpValidityTime.includes(':')) {
                    [minutes, seconds] = signInWithOtp.otpValidityTime.split(':').map(Number);
                } else {
                    minutes = Number(signInWithOtp.otpValidityTime) || 0;
                    seconds = 0;
                }

                SIOTP.interval = setInterval(() => {
                    if (minutes <= 0 && seconds <= 0) {
                        clearInterval(SIOTP.interval);
                        $('.countdown span').html(`<span class="signin_with_otp_minutes">00</span><small>:</small><span class="signin_with_otp_seconds">00</span>`);
                        $.post(signInWithOtp.ajaxUrl, {
                            action: 'disable_otp_after_validity',
                            user: $('#signin_with_otp_user').val()
                        }, function(response) {
                            $('#loginform').before(response.messageHtml);
                        });
                        return;
                    }

                    if (--seconds < 0) {
                        seconds = 59;
                        minutes--;
                    }

                    $('.countdown span').html(`
                        <span class="signin_with_otp_minutes">${String(minutes).padStart(2, '0')}</span>
                        <small>:</small>
                        <span class="signin_with_otp_seconds">${String(seconds).padStart(2, '0')}</span>
                    `);
                }, 1000);
            }
            
        };

        window.onloadCallback = function() {
            grecaptcha.render('html_element', {
                sitekey: signInWithOtp.greCaptcha.siteKey,
                callback: verifyCallback
            });
        };

        window.verifyCallback = function(response) {
            if (!response) return;

            clearInterval(SIOTP.interval);
            $('#signin_with_otp_captcha_wrap').addClass('si_processing');

            $.post(signInWithOtp.ajaxUrl, {
                action: 'generate_email_otp',
                user: $('#signin_with_otp_user').val()
            }, function(response) {
                $('#signin_with_otp_captcha_wrap').removeClass('si_processing');

                if (response.status === 'success') {
                    $('#login_error, .success, .message').remove();
                    $('#loginform').before(response.messageHtml);
                    SIOTP.sendOtp($('#signin_with_otp_user').val());
                } else {
                    $('#loginform').after(response.messageHtml);
                }
            });
        };

        SIOTP.init();
    });
});
