import {createLocalVue, shallowMount} from '@vue/test-utils';
import '../vueI18nfix.js';
import LoginSignupSwitcher from '../../js/components/LoginSignupSwitcher';
import moxios from 'moxios';
import axios from 'axios';
import Vuex from 'vuex';
import Vuelidate from 'vuelidate';

const $logger = {error: (val, params) => val, success: (val, params) => val};

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$logger = $logger;
            Vue.prototype.$t = (val) => val;
        },
    });
    localVue.use(Vuelidate);
    localVue.use(Vuex);
    return localVue;
}

/**
 * @param {Object} props
 * @return {Wrapper<Vue>}
 */
function mockLoginSignupSwitcher(props = {}) {
    const localVue = mockVue();
    const store = new Vuex.Store({
        modules: {
            websocket: {
                namespaced: true,
                actions: {
                    addOnOpenHandler: () => {},
                    addMessageHandler: () => {},
                },
            },
        },
    });
    const wrapper = shallowMount(LoginSignupSwitcher, {
        store,
        localVue: localVue,
        propsData: {
            ...props,
        },
    });

    return wrapper;
}

describe('LoginSignupSwitcher', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should render register and login form correctly', (done) => {
        const wrapper = mockLoginSignupSwitcher({googleRecaptchaSiteKey: 'any_fake_key_data'});

        moxios.stubRequest('login', {
            status: 200,
            response: 'login',
        });

        moxios.stubRequest('register', {
            status: 200,
            response: 'register',
        });

        moxios.wait(() => {
            expect(wrapper.findComponent('#login-form-container').html()).toContain('login');
            done();
        });
    });

    it('should compute loginLabel correctly', async () => {
        const wrapper = mockLoginSignupSwitcher();

        await wrapper.setData({loginForm: true});
        expect(wrapper.vm.loginLabel).toEqual('chat.modal.login.title');

        await wrapper.setData({loginForm: false});
        expect(wrapper.vm.loginLabel).toEqual('chat.modal.register.title');
    });

    it('should compute paddingClass correctly', async () => {
        const wrapper = mockLoginSignupSwitcher();

        await wrapper.setData({loginForm: true});
        expect(wrapper.vm.paddingClass).toEqual('');

        await wrapper.setData({loginForm: false});
        expect(wrapper.vm.paddingClass).toEqual('pl-3');
    });

    describe('loadRegisterForm', () => {
        it('should call other methods', async (done) => {
            const wrapper = mockLoginSignupSwitcher();

            const mountRegisterComponent = jest.spyOn(wrapper.vm, 'mountRegisterComponent').mockImplementation();
            const addEventListeners = jest.spyOn(wrapper.vm, 'addEventListenersForRegisterForm').mockImplementation();
            const removeAlreadyRegister = jest.spyOn(wrapper.vm, 'removeAlreadyRegisteredMessage').mockImplementation();
            const renderRecaptcha = jest.spyOn(wrapper.vm, 'renderRecaptcha').mockImplementation();

            moxios.stubRequest('register', {
                status: 200,
                response: 'register',
            });

            await wrapper.vm.loadRegisterForm();

            moxios.wait(() => {
                expect(mountRegisterComponent).toHaveBeenCalled();
                expect(addEventListeners).toHaveBeenCalled();
                expect(removeAlreadyRegister).toHaveBeenCalled();
                expect(renderRecaptcha).toHaveBeenCalled();
                done();
            });
        });
    });

    describe('removeAlreadyRegisteredMessage', () => {
        it('should remove the already registered message element', () => {
            const registerContainer = document.createElement('div');
            const messageElement = document.createElement('div');
            messageElement.id = 'registerFormAlreadyRegistered';
            registerContainer.appendChild(messageElement);

            const wrapper = mockLoginSignupSwitcher();
            wrapper.vm.registerContainer = registerContainer;

            wrapper.vm.removeAlreadyRegisteredMessage();

            expect(registerContainer.querySelector('#registerFormAlreadyRegistered')).toBeNull();
        });

        it('should not throw error if the message element is not found', () => {
            const registerContainer = document.createElement('div');

            const wrapper = mockLoginSignupSwitcher();
            wrapper.vm.registerContainer = registerContainer;

            // Simulate calling the method
            expect(() => wrapper.vm.removeAlreadyRegisteredMessage()).not.toThrow();
        });
    });

    describe('addEventListenersForRegisterForm', () => {
        it('should add event listeners for login button click and signup form submit', () => {
            const registerContainer = document.createElement('div');
            const loginButton = document.createElement('button');
            loginButton.id = 'login-button';
            const signupForm = document.createElement('form');
            signupForm.id = 'register';

            const wrapper = mockLoginSignupSwitcher();
            wrapper.vm.registerContainer = registerContainer;

            // Mock the DOM manipulation
            jest.spyOn(registerContainer, 'querySelector')
                .mockReturnValueOnce(loginButton)
                .mockReturnValueOnce(signupForm);

            // Mock the methods to be added as event listeners
            wrapper.vm.showLoginForm = jest.fn();
            wrapper.vm.onSignup = jest.fn();

            // Spy on the addEventListener method
            const addEventListenerSpy = jest.spyOn(loginButton, 'addEventListener');

            // Simulate calling the method
            wrapper.vm.addEventListenersForRegisterForm();

            expect(addEventListenerSpy).toHaveBeenCalledWith('click', expect.any(Function));

            // Simulate the click event on the login button
            loginButton.click();

            // Simulate the submit event on the signup form
            signupForm.dispatchEvent(new Event('submit'));

            expect(wrapper.vm.showLoginForm).toHaveBeenCalled();
            expect(wrapper.vm.onSignup).toHaveBeenCalled();
        });
    });

    describe('showLoginForm', () => {
        it('should set loginForm to true', () => {
            const wrapper = mockLoginSignupSwitcher();
            jest.spyOn(wrapper.vm, 'renderRecaptcha').mockImplementation();
            const event = {
                preventDefault: jest.fn(),
            };

            wrapper.vm.showLoginForm(event);

            expect(event.preventDefault).toHaveBeenCalled();
            expect(wrapper.vm.loginForm).toBe(true);
        });
    });

    describe('renderRecaptcha', () => {
        it('doesn\'t render recaptcha if container has child', () => {
            const wrapper = mockLoginSignupSwitcher();
            const loginRecaptchaSitekey = 'sitekey';
            const formContainer = document.createElement('div');
            formContainer.innerHTML = '<div class="g-recaptcha"><span></span></div>';

            const grecaptcha = {
                ready: jest.fn().mockImplementation((callback) => callback()),
                render: jest.fn(),
            };

            wrapper.vm.renderRecaptcha(formContainer, loginRecaptchaSitekey);

            expect(grecaptcha.ready).not.toHaveBeenCalled();
        });

        it('renders recaptcha if container doesn\'t have child', () => {
            const wrapper = mockLoginSignupSwitcher();
            const loginRecaptchaSitekey = 'sitekey';
            const formContainer = document.createElement('div');
            formContainer.innerHTML = '<div class="g-recaptcha"></div>';

            const grecaptcha = {
                ready: jest.fn().mockImplementation((callback) => callback()),
                render: jest.fn(),
            };

            global.grecaptcha = grecaptcha;

            wrapper.vm.renderRecaptcha(formContainer, loginRecaptchaSitekey);

            expect(grecaptcha.ready).toHaveBeenCalled();
        });
    });

    describe('onSignup', () => {
        it('should set formsLoaded to false and not call replaceComponentTemplate if registerFailed returns false',
            (done) => {
                const url = 'https://www.mintme.com/';

                Object.defineProperty(window, 'location', {
                    value: {
                        href: url,
                    },
                    configurable: true,
                });
                const replaceComponentTemplate = jest.fn();
                const wrapper = mockLoginSignupSwitcher({}, {
                    replaceComponentTemplate,
                    registerFailed: jest.fn().mockImplementation(() => false),
                });
                const formElement = document.createElement('form');
                const event = {
                    preventDefault: jest.fn(),
                    target: formElement,
                };

                moxios.stubRequest('register', {
                    status: 200,
                    response: 'register',
                });

                wrapper.vm.onSignup(event);

                moxios.wait(() => {
                    expect(event.preventDefault).toHaveBeenCalled();
                    expect(replaceComponentTemplate).not.toHaveBeenCalled();
                    expect(wrapper.vm.formsLoaded).toBe(false);
                    done();
                });
            });

        it('should set formsLoaded to true and call other methods if registerFailed returns true', (done) => {
            const wrapper = mockLoginSignupSwitcher();

            const replaceComponentTemplate = jest.spyOn(wrapper.vm, 'replaceComponentTemplate').mockImplementation();
            jest.spyOn(wrapper.vm, 'removeAlreadyRegisteredMessage').mockImplementation();
            jest.spyOn(wrapper.vm, 'renderRecaptcha').mockImplementation();
            jest.spyOn(wrapper.vm, 'addEventListenersForRegisterForm').mockImplementation();
            jest.spyOn(wrapper.vm, 'registerFailed').mockImplementation(() => true);

            const formElement = document.createElement('form');
            const event = {
                preventDefault: jest.fn(),
                target: formElement,
            };

            moxios.stubRequest('register', {
                status: 200,
                response: 'register',
            });

            wrapper.vm.onSignup(event);

            moxios.wait(() => {
                expect(event.preventDefault).toHaveBeenCalled();
                expect(replaceComponentTemplate).toHaveBeenCalled();
                expect(wrapper.vm.formsLoaded).toBe(true);
                done();
            });
        });
    });

    describe('registerFailed', () => {
        it('should return true if responseURL === register', () => {
            const wrapper = mockLoginSignupSwitcher();
            const res = {
                request: {
                    responseURL: 'register',
                },
            };

            const result = wrapper.vm.registerFailed(res);

            expect(result).toBe(true);
        });

        it('should return false if responseURL !== register', () => {
            const wrapper = mockLoginSignupSwitcher();
            const res = {
                request: {
                    responseURL: 'wrongURL',
                },
            };

            const result = wrapper.vm.registerFailed(res);

            expect(result).toBe(false);
        });
    });

    describe('loadLoginForm', () => {
        it('should call mountLoginComponent if request is successful', (done) => {
            const wrapper = mockLoginSignupSwitcher();
            const mountLoginComponent = jest.spyOn(wrapper.vm, 'mountLoginComponent').mockImplementation();

            moxios.stubRequest('login', {
                status: 200,
                response: 'login',
            });

            wrapper.vm.loadLoginForm();

            moxios.wait(() => {
                expect(mountLoginComponent).toHaveBeenCalled();
                done();
            });
        });

        it('should call logger.error if request fails', (done) => {
            const mountLoginComponent = jest.fn();
            const wrapper = mockLoginSignupSwitcher({}, {
                mountLoginComponent,
            });
            wrapper.vm.$logger = {error: jest.fn()};

            moxios.stubRequest('login', {
                status: 500,
                response: 'login',
            });

            wrapper.vm.loadLoginForm();

            moxios.wait(() => {
                expect(mountLoginComponent).not.toHaveBeenCalled();
                expect(wrapper.vm.$logger.error).toHaveBeenCalled();
                done();
            });
        });
    });

    describe('addEventListenersForLoginForm', () => {
        it('should add event listeners for login form', async () => {
            const wrapper = mockLoginSignupSwitcher();
            const showRegisterForm = jest.spyOn(wrapper.vm, 'showRegisterForm').mockImplementation();
            const onLogin = jest.spyOn(wrapper.vm, 'onLogin').mockImplementation();

            const loginContainer = document.createElement('div');

            const signupButton = document.createElement('button');
            signupButton.id = 'signup-button';

            const loginForm = document.createElement('form');
            loginForm.id = 'login';

            loginContainer.appendChild(signupButton);
            loginContainer.appendChild(loginForm);

            await wrapper.setData({loginContainer: loginContainer});

            loginForm.submit = jest.fn();
            wrapper.vm.addEventListenersForLoginForm();

            signupButton.click();
            loginForm.dispatchEvent(new Event('submit'));

            expect(showRegisterForm).toHaveBeenCalled();
            expect(onLogin).toHaveBeenCalled();
        });
    });

    describe('showRegisterForm', () => {
        it('should set loginForm to false', () => {
            const wrapper = mockLoginSignupSwitcher();
            const renderRecaptcha = jest.spyOn(wrapper.vm, 'renderRecaptcha').mockImplementation();

            const event = {
                preventDefault: jest.fn(),
            };

            wrapper.vm.showRegisterForm(event);

            expect(event.preventDefault).toHaveBeenCalled();
            expect(wrapper.vm.loginForm ).toBe(false);
            expect(renderRecaptcha).toHaveBeenCalled();
        });
    });

    describe('onLogin', () => {
        it('should set formsLoaded to false and not call replaceComponentTemplate if loginFailed returns false',
            (done) => {
                const url = 'https://www.mintme.com/';

                Object.defineProperty(window, 'location', {
                    value: {
                        href: url,
                    },
                    configurable: true,
                });
                const wrapper = mockLoginSignupSwitcher();
                const replaceComponent = jest.spyOn(wrapper.vm, 'replaceComponentTemplate').mockImplementation();
                jest.spyOn(wrapper.vm, 'loginFailed').mockImplementation(() => false);

                const formElement = document.createElement('form');
                const event = {
                    preventDefault: jest.fn(),
                    target: formElement,
                };

                moxios.stubRequest('fos_user_security_check', {
                    status: 200,
                    response: 'login',
                });

                wrapper.vm.onLogin(event);

                moxios.wait(() => {
                    expect(event.preventDefault).toHaveBeenCalled();
                    expect(replaceComponent).not.toHaveBeenCalled();
                    expect(wrapper.vm.formsLoaded).toBe(false);
                    done();
                });
            });

        it('should set formsLoaded to true and call other methods if loginFailed returns true', (done) => {
            const wrapper = mockLoginSignupSwitcher();

            const replaceComponentTemplate = jest.spyOn(wrapper.vm, 'replaceComponentTemplate').mockImplementation();
            jest.spyOn(wrapper.vm, 'loginFailed').mockImplementation(() => true);
            jest.spyOn(wrapper.vm, 'addEventListenersForLoginForm').mockImplementation();
            jest.spyOn(wrapper.vm, 'renderRecaptcha').mockImplementation();
            jest.spyOn(wrapper.vm, 'removeAlreadyRegisteredMessage').mockImplementation();

            const formElement = document.createElement('form');
            const event = {
                preventDefault: jest.fn(),
                target: formElement,
            };

            moxios.stubRequest('fos_user_security_check', {
                status: 200,
                response: 'login',
            });

            wrapper.vm.onLogin(event);

            moxios.wait(() => {
                expect(event.preventDefault).toHaveBeenCalled();
                expect(replaceComponentTemplate).toHaveBeenCalled();
                expect(wrapper.vm.formsLoaded).toBe(true);
                done();
            });
        });
    });

    describe('loginFailed', () => {
        it('should return true if responseURL === login', () => {
            const wrapper = mockLoginSignupSwitcher();
            const res = {
                request: {
                    responseURL: 'login',
                },
            };

            const result = wrapper.vm.loginFailed(res);

            expect(result).toBe(true);
        });

        it('should return false if responseURL !== login', () => {
            const wrapper = mockLoginSignupSwitcher();
            const res = {
                request: {
                    responseURL: 'wrongURL',
                },
            };

            const result = wrapper.vm.loginFailed(res);

            expect(result).toBe(false);
        });
    });
});
