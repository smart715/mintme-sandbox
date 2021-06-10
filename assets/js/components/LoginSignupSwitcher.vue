<template>
    <div>
        <div v-if="!formsLoaded" class="p-5 text-center">
            <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
        </div>
        <div ref="forms">
            <div v-show="loginForm">
                <div id="login-form-container" ref="login-form-container"></div>
            </div>
            <div v-show="!loginForm">
                <div id="register-form-container" ref="register-form-container"></div>
            </div>
        </div>
    </div>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faCircleNotch} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import Register from './Register';
import {LoggerMixin} from '../mixins';

library.add(faCircleNotch);

export default {
    name: 'LoginSignupSwitcher',
    components: {
        FontAwesomeIcon,
    },
    mixins: [LoggerMixin],
    props: {
        googleRecaptchaSiteKey: String,
        embeded: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            formsLoaded: false,
            loginForm: false,
        };
    },
    mounted() {
        let loadRegisterFormPromise = this.loadRegisterForm();
        let loadLoginFormPromise = this.loadLoginForm();

        Promise.all([loadRegisterFormPromise, loadLoginFormPromise]).then(() => this.formsLoaded = true);
    },
    methods: {
        loadRegisterForm: function() {
            return this.$axios.retry.get(this.$routing.generate('register', {
                formContentOnly: true,
                ...this.embeded ? {page: 'embeded'} : {},
            }))
                .then((res) => {
                    Register.template = res.data;

                    let Component = Vue.extend(Register);
                    let instance = new Component();
                    instance.$mount();

                    let registerContainer = this.$refs['register-form-container'];

                    registerContainer.appendChild(instance.$el);

                    let loginButton = registerContainer.querySelector('#login-button');
                    loginButton.addEventListener('click', (e) => {
                        e.preventDefault();
                        this.loginForm = true;
                    });

                    let signupForm = registerContainer.querySelector('#register');
                    signupForm.addEventListener('submit', (e) => {
                        this.$emit('signup');
                    });

                    let captchaContainer = registerContainer.querySelector('.g-recaptcha');
                    let googleRecaptchaSiteKey = this.googleRecaptchaSiteKey;

                    grecaptcha.ready(function() {
                        grecaptcha.render(captchaContainer, {
                            sitekey: googleRecaptchaSiteKey,
                        });
                    });
                })
                .catch((error) => {
                    this.sendLogs('error', 'Donation - can not load register form.', error);
                });
        },
        loadLoginForm: function() {
            return this.$axios.retry.get(this.$routing.generate('login', {
                formContentOnly: true,
                ...this.embeded ? {page: 'embeded'} : {},
            }))
                .then((res) => {
                    let loginContainer = this.$refs['login-form-container'];
                    loginContainer.innerHTML = res.data;

                    let captchaContainer = loginContainer.querySelector('.g-recaptcha');
                    let googleRecaptchaSiteKey = this.googleRecaptchaSiteKey;

                    let signupButton = loginContainer.querySelector('#signup-button');
                    signupButton.addEventListener('click', (e) => {
                        e.preventDefault();
                        this.loginForm = false;
                    });

                    let loginForm = loginContainer.querySelector('#login');
                    loginForm.addEventListener('submit', (e) => {
                        this.$emit('login');
                    });

                    grecaptcha.ready(function() {
                        grecaptcha.render(captchaContainer, {
                            sitekey: googleRecaptchaSiteKey,
                        });
                    });
                })
                .catch((error) => {
                    this.sendLogs('error', 'Donation - can not load login form.', error);
                });
        },
    },
};
</script>
