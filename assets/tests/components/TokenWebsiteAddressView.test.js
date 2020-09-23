import {shallowMount, createLocalVue} from '@vue/test-utils';
import TokenWebsiteAddressView from '../../js/components/token/website/TokenWebsiteAddressView';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.directive('b-tooltip', {});
    return localVue;
}

describe('TokenWebsiteAddressView', () => {
    it('show website link', () => {
        const wrapper = shallowMount(TokenWebsiteAddressView, {
            localVue: mockVue(),
            propsData: {currentWebsite: 'current_website'},
        });

        expect(wrapper.find('a').text()).toBe('current_website');
    });
});
