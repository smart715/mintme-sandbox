import {createLocalVue, shallowMount} from '@vue/test-utils';
import BackupCodeModal from '../../js/components/modal/BackupCodesModal.vue';
import AddPhoneAlertModal from '../../js/components/modal/AddPhoneAlertModal';
import {TIMERS} from '../../js/utils/constants';
import axios from 'axios';
import moxios from 'moxios';
import Vuex from 'vuex';
import Vuelidate from 'vuelidate';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.directive('html-sanitize', {});
    localVue.use(Vuelidate);
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
 * @return {Wrapper<Vue>}
 */
function createWrapper(props = {}, data = {}) {
    const localVue = mockVue();

    return shallowMount(BackupCodeModal, {
        localVue,
        propsData: props,
        data: () => data,
    });
}

describe('BackupCodeModal', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('emit "close" when the function onVerifyCodeEntered() is called and request success', (done) => {
        const wrapper = createWrapper({visible: true, havePhoneNumber: true});

        const downloadFileSpy = jest.spyOn(wrapper.vm, 'downloadFile');

        moxios.stubRequest('download_two_factor_backup_code', {
            status: 200,
            response: {
                data: {
                    file: 'text',
                    name: 'test.txt',
                },
            },
        });

        wrapper.vm.onPhoneCodeEntered('123456');

        moxios.wait(() => {
            expect(wrapper.emitted('close').length).toBe(1);
            expect(downloadFileSpy).toBeCalled();
            done();
        });
    });

    it(
        'does not emit "close" when the function onCodeEntered() is called and request failed',
        (done) => {
            const wrapper = createWrapper({visible: true});

            moxios.stubRequest('download_two_factor_backup_code', {
                status: 400,
                response: {
                    data: null,
                },
            });

            wrapper.vm.onPhoneCodeEntered('123456');

            moxios.wait(() => {
                expect(wrapper.emitted()['close']).toBeFalsy();
                done();
            });
        }
    );

    it('opens add phone modal when user has not phone added', async () => {
        const wrapper = createWrapper({visible: true});
        await wrapper.setData({havePhoneNumber: false});

        expect(wrapper.findComponent(AddPhoneAlertModal).exists()).toBeTruthy();

        await wrapper.setData({havePhoneNumber: true});

        expect(wrapper.findComponent(AddPhoneAlertModal).exists()).toBeFalsy();
    });

    it('should render verification page correctly', (done) => {
        const wrapper = createWrapper({visible: true});

        moxios.stubRequest('send_2fa_sms_verification_code', {
            status: 200,
            response: {
                data: null,
            },
        });

        wrapper.vm.requestPhoneCode();

        moxios.wait(() => {
            expect(wrapper.vm.showEnterCode).toEqual(true);
            expect(wrapper.vm.resendPhoneCodeText).toEqual('phone_confirmation.resend_code_in_secs');
            done();
        });
    });

    it('does not opens code verification view on failed send code firstly ', (done) => {
        const wrapper = createWrapper({visible: true});

        moxios.stubRequest('send_2fa_sms_verification_code', {
            status: 400,
            response: {
                data: null,
            },
        });

        wrapper.vm.requestPhoneCode();

        moxios.wait(() => {
            expect(wrapper.vm.showEnterCode).toEqual(false);
            done();
        });
    });

    it('should send code again on click', () => {
        const wrapper = createWrapper({visible: true});

        moxios.stubRequest('send_2fa_sms_verification_code', {
            status: 200,
            response: {
                data: null,
            },
        });

        expect(wrapper.vm.showEnterCode).toEqual(false);
        expect(wrapper.vm.resendPhoneCodeText).toEqual('phone_confirmation.send_code_again');

        wrapper.vm.requestPhoneCode();

        moxios.wait(() => {
            expect(wrapper.vm.resendPhoneCodeText).toEqual('phone_confirmation.resend_code_in_secs');
        });

        jest.useFakeTimers();
        jest.advanceTimersByTime(TIMERS.SEND_PHONE_CODE);

        expect(wrapper.vm.resendPhoneCodeText).toEqual('phone_confirmation.send_code_again');

        wrapper.vm.requestPhoneCode();

        jest.clearAllTimers();
    });
});
