import {createLocalVue, shallowMount} from '@vue/test-utils';
import PhoneNumber from '../../js/components/profile/PhoneNumber';
import {VueTelInput} from 'vue-tel-input';
import axios from 'axios';
import moxios from 'moxios';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$logger = {error: () => {}};
            Vue.prototype.$toasted = {show: () => {}};
        },
    });

    return localVue;
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createPhoneNumber(props = {}) {
    return {
        phoneNumber: '+584124751551',
        inline: false,
        disabled: false,
        editLimitReached: false,
        inputTabIndex: '0',
        ...props,
    };
}

describe('PhoneNumber', () => {
    let wrapper;

    beforeEach(() => {
        moxios.install();

        wrapper = shallowMount(PhoneNumber, {
            localVue: localVue,
            propsData: createPhoneNumber(),
            directives: {
                'b-tooltip': {},
            },
        });
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should toggle input focus', () => {
        wrapper.vm.toggleInputFocus();

        expect(wrapper.vm.isFocused).toBeTruthy();
    });

    it('should update phone', () => {
        const phone = wrapper.vm.phoneNumber;

        wrapper.vm.updatePhone(phone, {valid: true, number: phone});

        expect(wrapper.vm.isValidNumber).toBeTruthy();
        expect(wrapper.vm.isPhoneBlocked).toBeFalsy();
        expect(wrapper.vm.phoneChecking).toBeFalsy();
        expect(wrapper.vm.phone).toBe('+584124751551');
    });

    it('should check phone on valid input', () => {
        const debouncedPhoneCheckStub = jest.fn();

        wrapper.vm.debouncedPhoneCheck = debouncedPhoneCheckStub;

        const telInput = wrapper.findComponent(VueTelInput);

        expect(telInput.exists()).toBeTruthy();

        telInput.vm.$emit('input', '+1234567890', {valid: true, number: '+1234567890'});

        expect(debouncedPhoneCheckStub).toBeCalledWith('+1234567890');
    });

    it('should not should check phone on valid input', () => {
        const debouncedPhoneCheckStub = jest.fn();

        wrapper.vm.debouncedPhoneCheck = debouncedPhoneCheckStub;

        const telInput = wrapper.findComponent(VueTelInput);

        expect(telInput.exists()).toBeTruthy();

        telInput.vm.$emit('input', '+1234567890', {valid: false, number: '+1234567890'});

        expect(debouncedPhoneCheckStub).not.toBeCalled();
    });

    it('should check phone validity with axios status', (done) => {
        const phone = wrapper.vm.phoneNumber;

        moxios.stubRequest('check_phone_in_use', {
            status: 200,
            response: {
                data: {
                    in_use: true,
                },
            },
        });

        wrapper.vm.checkPhoneValidity(phone);

        moxios.wait(() => {
            expect(wrapper.vm.isPhoneBlocked).toBeTruthy();
            expect(wrapper.vm.phoneChecking).toBeFalsy();
            done();
        });
    });

    it('should check token in cancelTokenSource', () => {
        wrapper.vm.cancelTokenSource = {
            cancel: jest.fn(),
            token: {
                promise: Promise.resolve(),
            },
        };

        expect(wrapper.vm.cancelTokenSource.token.promise).toBeInstanceOf(Promise);
    });

    it('should check cancelPhoneCheckRequest', () => {
        wrapper.vm.cancelTokenSource = {
            cancel: jest.fn(),
            token: {
                promise: Promise.resolve(),
            },
        };

        wrapper.vm.cancelPhoneCheckRequest();

        expect(wrapper.vm.cancelTokenSource.cancel).toBeCalled();
    });

    it('should display error message when its not valid', (done) => {
        const errorSpy = jest.spyOn(wrapper.vm, 'notifyError');
        moxios.stubRequest('check_phone_in_use', {
            status: 400,
        });

        wrapper.vm.checkPhoneValidity(wrapper.vm.phoneNumber);

        moxios.wait(() => {
            expect(errorSpy).toBeCalled();
            done();
        });
    });

    it('should display spinner when requesting', () => {
        const telInput = wrapper.findComponent(VueTelInput);

        expect(telInput.exists()).toBeTruthy();

        telInput.vm.$emit('input', '+1234567890', {valid: true, number: '+1234567890'});

        expect(wrapper.vm.phoneChecking).toBeTruthy();
        expect(wrapper.vm.spinnerClass).toBe('visible');
    });
});
