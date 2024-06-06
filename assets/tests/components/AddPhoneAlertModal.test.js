import {createLocalVue, shallowMount} from '@vue/test-utils';
import AddPhoneAlertModal from '../../js/components/modal/AddPhoneAlertModal';
import axios from 'axios';
import moxios from 'moxios';
import SessionStorage from '../__mocks__/SessionStorage';
import user from '../../js/storage/modules/user';
import Vuex from 'vuex';

Object.defineProperty(window, 'sessionStorage', {
    value: new SessionStorage(),
});

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.directive('html-sanitize', {});
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$toasted = {show: (val) => {}};
            Vue.prototype.$logger = {error: (val) => {}};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$toasted = {show: () => {}};
        },
    });
    localVue.use(Vuex);
    return localVue;
}

/**
 * @param {Object} props
 * @param {Object} data
 * @param {Vuex.Store} store
 * @return {Wrapper<Vue>}
 */
function createWrapper(props = {}, data = {}, store = null) {
    const localVue = mockVue();

    return shallowMount(AddPhoneAlertModal, {
        store: store ?? createSharedTestStore(),
        localVue,
        propsData: props,
        data: () => data,
    });
}

/**
 * @return {Vuex.Store}
 */
function createSharedTestStore() {
    return new Vuex.Store({
        modules: {
            user: {
                ...user,
                state: {
                    hasPhoneVerified: false,
                    isPhoneVerificationPending: false,
                },
            },
        },
    });
}

describe('AddPhoneAlertModal', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should be visible when visible props is true', () => {
        const wrapper = createWrapper({visible: true});

        expect(wrapper.vm.visible).toBe(true);
    });

    it('renders confirm button correctly on verify phone loading', async (done) => {
        const wrapper = createWrapper({visible: true});

        moxios.stubRequest('verify_phone_number', {
            status: 200,
            response: {},
        });

        expect(wrapper.findComponent('.spinner-border').exists()).toBeFalsy();
        expect(wrapper.vm.confirmText).toBe('modal.add_phone_alert.verify');

        wrapper.vm.onPhoneCodeEntered('123456');
        wrapper.vm.onEmailCodeEntered('123456');

        expect(wrapper.vm.confirmText).toBe('');

        moxios.wait(() => {
            expect(wrapper.vm.confirmText).toBe('modal.add_phone_alert.verify');
            expect(wrapper.findComponent('.spinner-border').exists()).toBeFalsy();
            done();
        });
    });

    it('emit "phone-verified" when the function onVerifyCodeEntered() is called and request success', (done) => {
        const wrapper = createWrapper({visible: true});

        const setHasPhoneVerifiedSpy = jest.spyOn(wrapper.vm, 'setHasPhoneVerified');

        moxios.stubRequest('verify_phone_number', {
            status: 200,
            response: {},
        });

        wrapper.vm.onPhoneCodeEntered('123456');
        wrapper.vm.onEmailCodeEntered('123456');

        moxios.wait(() => {
            expect(wrapper.emitted('phone-verified').length).toBe(1);
            expect(setHasPhoneVerifiedSpy).toBeCalled();
            done();
        });
    });

    it(
        'does not emit "phone-verified" when the function onCodeEntered() is called and request failed',
        (done) => {
            const wrapper = createWrapper({visible: true});

            moxios.stubRequest('verify_phone_number', {
                status: 400,
                response: {
                    data: null,
                },
            });

            wrapper.vm.onPhoneCodeEntered('123456');
            wrapper.vm.onEmailCodeEntered('123456');

            moxios.wait(() => {
                expect(wrapper.emitted()['phone-verified']).toBeFalsy();
                done();
            });
        }
    );

    it('opens code verification view on success phone add', (done) => {
        const wrapper = createWrapper({visible: true});

        moxios.stubRequest('add_phone_number', {
            status: 200,
            response: {
                data: null,
            },
        });
        moxios.stubRequest('send_phone_verification_code', {
            status: 200,
            response: {
                data: null,
            },
        });
        moxios.stubRequest('send_mail_phone_verification_code', {
            status: 200,
            response: {
                data: null,
            },
        });

        wrapper.vm.phoneChange('+123567893465');
        expect(wrapper.vm.phoneNumber).toEqual('+123567893465');

        wrapper.vm.verifyNumber();

        moxios.wait(() => {
            expect(wrapper.vm.showEnterCode).toEqual(true);
            done();
        });
    });

    it('should render verification page correctly', (done) => {
        const wrapper = createWrapper({visible: true});

        moxios.stubRequest('add_phone_number', {
            status: 200,
            response: {
                data: null,
            },
        });
        moxios.stubRequest('send_phone_verification_code', {
            status: 200,
            response: {
                data: null,
            },
        });
        moxios.stubRequest('send_mail_phone_verification_code', {
            status: 200,
            response: {
                data: null,
            },
        });

        wrapper.vm.phoneChange('+123567893465');
        expect(wrapper.vm.phoneNumber).toEqual('+123567893465');

        wrapper.vm.verifyNumber();

        moxios.wait(() => {
            expect(wrapper.vm.showEnterCode).toEqual(true);
            expect(wrapper.vm.resendPhoneCodeText).toEqual('phone_confirmation.resend_code_in_secs');
            expect(wrapper.vm.resendEmailCodeText).toEqual('phone_confirmation.resend_code_in_secs');
            done();
        });
    });

    it('should render modal with action session phone number correctly', () => {
        const store = createSharedTestStore();
        store.commit('user/setIsPhoneVerificationPending', true);

        const wrapper = createWrapper({visible: true}, {}, store);

        expect(wrapper.vm.showEnterCode).toEqual(true);
        expect(wrapper.vm.resendPhoneCodeText).toEqual('phone_confirmation.send_code_again');
        expect(wrapper.vm.resendEmailCodeText).toEqual('phone_confirmation.send_code_again');
    });

    it('should send code again on click', (done) => {
        const store = createSharedTestStore();
        store.commit('user/setIsPhoneVerificationPending', true);

        const wrapper = createWrapper({visible: true}, {}, store);

        moxios.stubRequest('send_phone_verification_code', {
            status: 200,
            response: {
                data: null,
            },
        });
        moxios.stubRequest('send_mail_phone_verification_code', {
            status: 200,
            response: {
                data: null,
            },
        });

        expect(wrapper.vm.showEnterCode).toEqual(true);
        expect(wrapper.vm.btnDisabled).toEqual(true);

        wrapper.vm.requestPhoneCode();
        wrapper.vm.requestEmailCode();

        expect(wrapper.vm.btnDisabled).toEqual(true);

        wrapper.vm.validPhone(true);

        moxios.wait(() => {
            expect(wrapper.vm.showEnterCode).toEqual(true);
            expect(wrapper.vm.btnDisabled).toEqual(true);
            done();
        });
    });

    it('does not opens code verification view on failed phone add', (done) => {
        const wrapper = createWrapper({visible: true});

        moxios.stubRequest('add_phone_number', {
            status: 200,
            response: {
                data: null,
            },
        });
        moxios.stubRequest('send_phone_verification_code', {
            status: 400,
            response: {
                data: null,
            },
        });
        moxios.stubRequest('send_mail_phone_verification_code', {
            status: 400,
            response: {
                data: null,
            },
        });

        wrapper.vm.phoneChange('+123567893465');
        expect(wrapper.vm.phoneNumber).toEqual('+123567893465');

        wrapper.vm.verifyNumber();

        moxios.wait(() => {
            expect(wrapper.vm.showEnterCode).toEqual(false);
            done();
        });
    });
});
