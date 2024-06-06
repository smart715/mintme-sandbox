<template>
    <div>
        <div v-show="!formsLoaded" class="p-5 text-center">
            <font-awesome-icon icon="circle-notch" spin class="loading-spinner" fixed-width />
        </div>
        <div v-show="formsLoaded" ref="forms">
            <div
                v-if="showLabel && formsLoaded"
                class="text-dark h3"
                :class="paddingClass"
            >
                {{ loginLabel }}
                <hr class="h-divider mt-2" />
            </div>
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
import Login from './Login';

library.add(faCircleNotch);

export default {
    name: 'LoginSignupSwitcher',
    components: {
        FontAwesomeIcon,
    },
    props: {
        loginRecaptchaSitekey: String,
        regRecaptchaSitekey: String,
        embeded: {
            type: Boolean,
            default: false,
        },
        showLabel: {
            type: Boolean,
            default: false,
        },
        isAirdropReferral: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            formsLoaded: false,
            loginForm: false,
            registerComponent: null,
            registerContainer: null,
            loginComponent: null,
            loginContainer: null,
        };
    },
    async mounted() {
        this.registerContainer = this.$refs['register-form-container'];
        this.loginContainer = this.$refs['login-form-container'];

        await Promise.all([
            this.loadRegisterForm(),
            this.loadLoginForm(),
        ]);

        this.formsLoaded = true;
    },
    computed: {
        loginLabel() {
            return this.loginForm
                ? this.$t('chat.modal.login.title')
                : this.$t('chat.modal.register.title');
        },
        paddingClass() {
            return !this.loginForm ? 'pl-3' : '';
        },
        registerFormRequestUrl() {
            const params = {
                formContentOnly: true,
                withReferral: this.isAirdropReferral,
                ...this.embeded ? {page: 'embeded'} : {},
            };

            return this.$routing.generate('register', params, true);
        },
        loginFormRequestUrl() {
            const params = {
                formContentOnly: true,
                ...this.embeded ? {page: 'embeded'} : {},
            };
            return this.$routing.generate('login', params, true);
        },
    },
    methods: {
        async loadRegisterForm() {
            try {
                const res = await this.$axios.retry.get(this.registerFormRequestUrl);

                this.mountRegisterComponent(res.data);
                this.removeAlreadyRegisteredMessage();
                this.addEventListenersForRegisterForm();
                this.renderRecaptcha(this.registerContainer, this.regRecaptchaSitekey);
            } catch (error) {
                this.$logger.error('Donation - can not load register form.', error);
            }
        },
        mountRegisterComponent(template) {
            Register.template = template;
            const Component = Vue.extend(Register);
            const instance = new Component();
            instance.$mount();
            this.registerComponent = instance;
            this.registerContainer.appendChild(instance.$el);
        },
        removeAlreadyRegisteredMessage() {
            this.registerContainer.querySelector('#registerFormAlreadyRegistered')?.remove();
        },
        addEventListenersForRegisterForm() {
            const loginButton = this.registerContainer.querySelector('#login-button');
            loginButton.addEventListener('click', this.showLoginForm);

            const signupForm = this.registerContainer.querySelector('#register');
            signupForm.addEventListener('submit', this.onSignup);
        },
        showLoginForm(event) {
            event.preventDefault();
            this.loginForm = true;
            this.renderRecaptcha(this.loginContainer, this.loginRecaptchaSitekey);
        },
        renderRecaptcha(formContainer, recaptchaSitekey) {
            const captchaContainer = formContainer.querySelector('.g-recaptcha');

            if (captchaContainer.firstChild) {
                return;
            }

            grecaptcha.ready(() =>
                grecaptcha.render(captchaContainer, {
                    sitekey: recaptchaSitekey,
                    theme: 'white',
                })
            );
        },
        async onSignup(event) {
            event.preventDefault();
            this.formsLoaded = false;
            this.$emit('signup');

            const data = new FormData(event.target);
            const res = await this.$axios.single.post(this.registerFormRequestUrl, data);

            if (!this.registerFailed(res)) {
                window.location.href = res.request.responseURL;
                return;
            }

            await this.replaceComponentTemplate(this.registerComponent, res.data);

            this.removeAlreadyRegisteredMessage();
            this.renderRecaptcha(this.registerContainer, this.regRecaptchaSitekey);
            this.addEventListenersForRegisterForm();
            this.formsLoaded = true;
        },
        registerFailed(res) {
            return res.request.responseURL === this.registerFormRequestUrl;
        },
        async replaceComponentTemplate(component, template) {
            const {render, staticRenderFns} = Vue.compile(template);
            component.$options.render = render;
            component.$options.staticRenderFns = staticRenderFns;
            component._staticTrees = [];
            component.$forceUpdate();
            await component.$nextTick();
        },
        async loadLoginForm() {
            try {
                const res = await this.$axios.retry.get(this.loginFormRequestUrl);

                this.mountLoginComponent(res.data);
                this.addEventListenersForLoginForm();
            } catch (error) {
                this.$logger.error('Donation - can not load login form.', error);
            };
        },
        mountLoginComponent(template) {
            Login.template = template;
            const Component = Vue.extend(Login);
            const instance = new Component();
            instance.$mount();
            this.loginComponent = instance;
            this.loginContainer.appendChild(instance.$el);
        },
        addEventListenersForLoginForm() {
            const signupButton = this.loginContainer.querySelector('#signup-button');
            signupButton.addEventListener('click', this.showRegisterForm);

            const loginForm = this.loginContainer.querySelector('#login');
            loginForm.addEventListener('submit', this.onLogin);
        },
        showRegisterForm(event) {
            event.preventDefault();
            this.loginForm = false;
            this.renderRecaptcha(this.registerContainer, this.regRecaptchaSitekey);
        },
        async onLogin(event) {
            event.preventDefault();
            this.formsLoaded = false;
            this.$emit('login');

            const data = new FormData(event.target);
            data.append('_failure_path', this.loginFormRequestUrl);
            const res = await this.$axios.single.post(this.$routing.generate('fos_user_security_check'), data);

            if (!this.loginFailed(res)) {
                window.location.href = res.request.responseURL;
                return;
            }

            await this.replaceComponentTemplate(this.loginComponent, res.data);
            this.addEventListenersForLoginForm();
            this.renderRecaptcha(this.loginContainer, this.loginRecaptchaSitekey);

            this.formsLoaded = true;
        },
        loginFailed(response) {
            return response.request.responseURL === this.loginFormRequestUrl;
        },
    },
};
</script>
