import Vue from 'vue';
import {createLocalVue, mount} from '@vue/test-utils';
import Vuelidate from 'vuelidate';
import Toasted from 'vue-toasted';
import TokenEditModal from '../../js/components/modal/TokenEditModal';
import moxios from 'moxios';
import axios from 'axios';
Vue.use(Vuelidate);
Vue.use(Toasted);

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(axios);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
        },
    });
    return localVue;
}

describe('TokenEditModal', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('renders correctly with assigned props', () => {
        const wrapper = mount(TokenEditModal, {
            propsData: {
                visible: true,
                currentName: 'foobar',
                deleteUrl: 'deleteUrl',
                sendCodeUrl: 'sendCodeUrl',
                twofa: false,
                updateUrl: 'updateUrl',
            },
        });
        const textInput = wrapper.find('input');

        expect(wrapper.vm.visible).to.equal(true);
        expect(wrapper.vm.currentName).to.equal('foobar');
        expect(wrapper.vm.newName).to.equal('foobar');
        expect(textInput.element.value).to.equal('foobar');
    });

    it('throw required error when value is not set', () => {
        const wrapper = mount(TokenEditModal, {
            propsData: {
                visible: true,
                currentName: 'foobar',
                deleteUrl: 'deleteUrl',
                sendCodeUrl: 'sendCodeUrl',
                twofa: false,
                updateUrl: 'updateUrl',
            },
        });
        const textInput = wrapper.find('input');

        textInput.setValue('');
        wrapper.vm.editName();
        wrapper.vm.$v.$touch();
        expect(wrapper.vm.$v.$error).to.deep.equal(true);
    });

    it('open TwoFactorModal for saving name when 2fa is enabled', () => {
        const wrapper = mount(TokenEditModal, {
            propsData: {
                visible: true,
                currentName: 'foobar',
                deleteUrl: 'deleteUrl',
                sendCodeUrl: 'sendCodeUrl',
                twofa: true,
                updateUrl: 'updateUrl',
            },
        });
        expect(wrapper.vm.showTwoFactorModal).to.deep.equal(false);
        wrapper.find('input').setValue('newName');
        wrapper.find('.btn-primary').trigger('click');
        expect(wrapper.vm.mode).to.deep.equal('edit');
        expect(wrapper.vm.showTwoFactorModal).to.deep.equal(true);
    });

    it('do not open TwoFactorModal for saving name when 2fa is disabled', () => {
        const wrapper = mount(TokenEditModal, {
            propsData: {
                visible: true,
                currentName: 'foobar',
                deleteUrl: 'deleteUrl',
                sendCodeUrl: 'sendCodeUrl',
                twofa: false,
                updateUrl: 'updateUrl',
            },
        });
        expect(wrapper.vm.showTwoFactorModal).to.deep.equal(false);
        wrapper.find('input').setValue('newName');
        wrapper.find('.btn-primary').trigger('click');
        expect(wrapper.vm.mode).to.deep.equal(null);
        expect(wrapper.vm.showTwoFactorModal).to.deep.equal(false);
    });

    it('open TwoFactorModal for token deletion', () => {
        const wrapper = mount(TokenEditModal, {
            propsData: {
                visible: true,
                currentName: 'foobar',
                deleteUrl: 'deleteUrl',
                sendCodeUrl: 'sendCodeUrl',
                twofa: true,
                updateUrl: 'updateUrl',
            },
        });
        wrapper.findAll('.btn-cancel').at(1).trigger('click');
        expect(wrapper.vm.mode).to.deep.equal('delete');
        expect(wrapper.vm.showTwoFactorModal).to.deep.equal(true);
    });

    it('do not need to send auth code when 2fa enabled', () => {
        const wrapper = mount(TokenEditModal, {
            propsData: {
                visible: true,
                currentName: 'foobar',
                deleteUrl: 'deleteUrl',
                sendCodeUrl: 'sendCodeUrl',
                twofa: true,
                updateUrl: 'updateUrl',
            },
        });

        expect(wrapper.vm.needToSendCode).to.equal(false);
    });

    it('need to send auth code when 2fa disabled', () => {
        const wrapper = mount(TokenEditModal, {
            propsData: {
                visible: true,
                currentName: 'foobar',
                deleteUrl: 'deleteUrl',
                sendCodeUrl: 'sendCodeUrl',
                twofa: false,
                updateUrl: 'updateUrl',
            },
        });

        expect(wrapper.vm.needToSendCode).to.equal(true);
    });

    it('do not need send auth code when it already sent', (done) => {
        const localVue = mockVue();
        const wrapper = mount(TokenEditModal, {
            localVue,
            propsData: {
                visible: true,
                currentName: 'foobar',
                deleteUrl: 'deleteUrl',
                sendCodeUrl: 'sendCodeUrl',
                twofa: true,
                updateUrl: 'updateUrl',
            },
        });

        moxios.stubRequest('token_send_code', {
            status: 200,
            message: 'message',
        });

        moxios.wait(() => {
            expect(wrapper.vm.needToSendCode).to.equal(false);
            done();
        });
    });
});
