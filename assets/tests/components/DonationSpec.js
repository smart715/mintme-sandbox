import {createLocalVue, mount} from '@vue/test-utils';
import Donation from '../../js/components/donation/Donation';
import moxios from 'moxios';
import axios from 'axios';

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

describe('Donation', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should renders correctly for logged in user', () => {
        const wrapper = mount(Donation, {
            propsData: {
                loggedIn: true,
            },
        });

        expect(wrapper.find('#tab-login-form-container').exists()).to.equal(true);
        expect(wrapper.vm.loginFormContainerClass).to.equal('');
        expect(wrapper.vm.dropdownText).to.equal('Select currency');
        expect(wrapper.vm.isCurrencySelected).to.be.false;
        expect(wrapper.vm.loginFormLoaded).to.be.true;
        expect(wrapper.vm.buttonDisabled).to.be.true;
        expect(wrapper.find('.donation-header span').text()).to.equal('Donations');
        expect(wrapper.find('b-dropdown').exists()).to.deep.equal(true);
    });

    it('should renders correctly for not logged in user', () => {
        const localVue = mockVue();
        const wrapper = mount(Donation, {
            localVue,
            propsData: {
                loggedIn: false,
            },
        });

        expect(wrapper.find('#tab-login-form-container').exists()).to.equal(true);
        expect(wrapper.vm.loginFormContainerClass).to.equal('p-md-4');
        expect(wrapper.vm.loginFormLoaded).to.be.false;
        expect(wrapper.vm.buttonDisabled).to.be.true;
        expect(wrapper.vm.dropdownText).to.equal('Select currency');
        expect(wrapper.find('.donation-header span').text()).to.equal('To make a donation you have to be logged in');
        expect(wrapper.find('b-dropdown').exists()).to.deep.equal(false);

        moxios.stubRequest('login', {
            status: 200,
            response: {data: '<form></form>'},
        });
    });
});
