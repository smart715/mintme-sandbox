import Vue from 'vue';
import {shallowMount, createLocalVue} from '@vue/test-utils';
import TokenFacebookAddressView from '../../js/components/token/facebook/TokenFacebookAddressView';

Vue.use({
    install(Vue, options) {
        Vue.prototype.$t = (val) => val;
    },
});

describe('TokenFacebookAddressView', () => {
    it('show facebook link and button', () => {
        const localVue = createLocalVue();

        const wrapper = shallowMount(TokenFacebookAddressView, {
            localVue,
            propsData: {address: 'facebook_url'},
        });

        expect(wrapper.findAll('a').at(0).text()).toBe('facebook_url');
        expect(wrapper.findAll('a').at(1).attributes('href')).toBe('https://www.facebook.com/sharer/sharer.php?u=facebook_url&amp;src=sdkpreparse');
    });
});
