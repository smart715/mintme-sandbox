<template>
    <div>
        <div v-if="!loginFormLoaded" class="p-5 text-center">
            <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
        </div>
        <div ref="forms">
            <div v-show="loginForm">
                <div ref="login-form-container">
                    <form action="/login" method="post" id="login">
                        <div class="form-group row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-12  text-left">
                                        <label for="inputEmail" class="d-block label-featured">Email:</label>
                                    </div>
                                    <div class="col">
                                        <input type="email"
                                            name="_username"
                                            pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                                            value =""
                                            required="required"
                                            id="inputEmail"
                                            class="form-control"
                                            autofocus>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-12  text-left">
                                        <label for="inputPassword" class="d-block label-featured">
                                            Password:
                                        </label>
                                    </div>
                                    <div id="login-password" class="col">
                                        <input type="password"
                                            autocomplete="off"
                                            name="_password"
                                            required="required"
                                            id="inputPassword"
                                            class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div ref="login-captcha-container"></div>
                        <div class="form-group row ">
                            <div class="col-md-12 text-left">
                                <input type="submit"
                                    id="_submit"
                                    name="_submit"
                                    class="btn btn-primary rounded-0"
                                    value="Log In">
                                <span class="px-2">
                                    or
                                </span>
                                <a class="btn-cancel px-0"
                                    href="#"
                                    @click.prevent="loginForm=false">Sign Up</a>
                            </div>
                        </div>
                        <div class="text-left text-white">
                            <a href="/resetting/request">Forgot password?</a>
                        </div>
                        <div ref="login-hidden"></div>
                    </form>
               </div>
            </div>
            <div v-show="!loginForm">
                <div ref="register-form-container">
                    <form name="fos_user_registration_form"
                        method="post"
                        action="/register/"
                        class="fos_user_registration_register"
                        id="register">
                        <div class="form-group">
                            <label for="fos_user_registration_form_email" class="required">
                                Email:
                            </label>
                            <input type="email"
                                id="fos_user_registration_form_email"
                                name="fos_user_registration_form[email]"
                                required="required"
                                pattern="[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$"
                                class="form-control
                                 form-control-md">
                        </div>
                        <label for="fos_user_registration_form_nickname" class="required">
                            Nickname (Alias):
                        </label>
                        <guide>
                            <template slot="header">
                                Nickname
                            </template>
                            <template slot="body">
                                Name used in our service, visible for everyone.
                            </template>
                        </guide>
                        <input
                            ref="nickname"
                            type="text"
                            id="fos_user_registration_form_nickname"
                            name="fos_user_registration_form[nickname]"
                            required="required"
                            minlength="2"
                            maxlength="30"
                            pattern="[A-Za-z\d]+"
                            v-model.trim="$v.nickname.$model"
                            class="form-control
                            form-control-md">
                        <div v-cloak v-if="!$v.nickname.minLength" class="text-danger text-center small">
                            Minimum length must be 2
                        </div>
                        <div v-cloak v-if="!$v.nickname.helpers" class="text-danger text-center small">
                            Nickname can contain only latin letters and numbers
                        </div>
                        <div id="register-form-password-container">
                            <passwordmeter :password="password" @toggle-error="toggleError">
                                <span @click="togglePassword()" class="show-password">
                                    <i class="far fa-eye"></i>
                                </span>
                                <div class="form-group">
                                    <label for="fos_user_registration_form_plainPassword" class="required">
                                        Password:
                                    </label>
                                    <input type="password"
                                        id="fos_user_registration_form_plainPassword"
                                        name="fos_user_registration_form[plainPassword]"
                                        required="required"
                                        v-model="password"
                                        class="form-control form-control-md">
                                </div>
                            </passwordmeter>
                        </div>
                        <div ref="register-captcha-container"></div>
                        <div class="text-left">
                            <input type="submit" :disabled="disabled" class="btn btn-primary" value="Sign Up">
                            <span class="px-3">
                                or
                            </span>
                            <a class="btn-cancel px-0" href="#" @click.prevent="loginForm=true">
                                Log In
                            </a>
                        </div>
                        <p class="text-left pt-3">
                            By clicking Sign Up, you accept
                            <br>
                            <a target="_blank" href="/terms-of-service">
                                Terms of Service
                            </a>
                            and
                            <a target="_blank" href="/privacy-policy">
                                Privacy policy
                            </a>
                        </p>
                        <div ref="register-hidden"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {minLength} from 'vuelidate/lib/validators';
import {nickname} from '../utils/constants';
import Passwordmeter from './PasswordMeter';
import Guide from './Guide';

export default {
    name: 'Register',
    components: {
        Passwordmeter,
        Guide,
    },
    props: {
        googleRecaptchaSiteKey: String,
    },
    data() {
        return {
            loginFormLoaded: false,
            nickname: '',
            password: '',
            disabled: false,
            passwordInput: null,
            isPass: true,
            eyeIcon: null,
            loginForm: false,
        };
    },
    mounted() {
        this.passwordInput = document.getElementById('fos_user_registration_form_plainPassword');
        this.eyeIcon = document.querySelector('.show-password');
        this.loadRegisterForm();
        this.loadLoginForm();
        this.loginFormLoaded = true;
    },
    methods: {
        loadRegisterForm: function() {
            this.$axios.retry.get(this.$routing.generate('register', {
                formContentOnly: true,
            }))
                .then((res) => {
                    let temp = document.createElement('div');
                    temp.innerHTML = res.data;
                    temp = temp.querySelector('#register');
                    this.$refs['register-captcha-container'].innerHTML = temp.querySelector('#register-form-captcha-container').outerHTML;
                    this.$refs['register-hidden'].innerHTML = temp.querySelector('#fos_user_registration_form__token').outerHTML;
                    let captchaContainer = this.$refs['register-captcha-container'].querySelector('.g-recaptcha');
                    let googleRecaptchaSiteKey = this.googleRecaptchaSiteKey;
                    grecaptcha.ready(function() {
                        grecaptcha.render(captchaContainer, {
                            'sitekey': googleRecaptchaSiteKey,
                        });
                    });
                })
                .catch((error) => {
                    this.notifyError('Something went wrong. Try to reload the page.');
                    this.sendLogs('error', 'Can not load tab content.', error);
                });
        },
        loadLoginForm: function() {
            this.$axios.retry.get(this.$routing.generate('login', {
                formContentOnly: true,
            }))
                .then((res) => {
                    let temp = document.createElement('div');
                    temp.innerHTML = res.data;
                    temp = temp.querySelector('#login');
                    this.$refs['login-captcha-container'].innerHTML = temp.querySelector('#login-form-captcha-container').outerHTML;
                    this.$refs['login-hidden'].innerHTML = temp.querySelector('#fos_user_registration_login__token').outerHTML;
                    let captchaContainer = this.$refs['login-captcha-container'].querySelector('.g-recaptcha');
                    let googleRecaptchaSiteKey = this.googleRecaptchaSiteKey;
                    grecaptcha.ready(function() {
                        grecaptcha.render(captchaContainer, {
                            'sitekey': googleRecaptchaSiteKey,
                        });
                    });
                })
                .catch((error) => {
                    this.notifyError('Something went wrong. Try to reload the page.');
                    this.sendLogs('error', 'Can not load tab content.', error);
                });
        },
        toggleError: function(val) {
            this.disabled = val;
        },
        togglePassword: function() {
            if (this.isPass) {
                this.passwordInput.type = 'text';
                this.eyeIcon.className = 'show-password-active';
                this.isPass = false;
            } else {
                this.passwordInput.type = 'password';
                this.eyeIcon.className = 'show-password';
                this.isPass = true;
            }
        },
    },
    validations: {
        nickname: {
            helpers: nickname,
            minLength: minLength(2),
        },
    },
};
</script>
