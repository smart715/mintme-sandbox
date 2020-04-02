import {mount} from '@vue/test-utils';
import TokenWebsiteAddressView from '../../js/components/token/website/TokenWebsiteAddressView';

describe('TokenWebsiteAddressView', () => {
    it('show website link', () => {
        const wrapper = mount(TokenWebsiteAddressView, {
            propsData: {currentWebsite: 'current_website'},
        });

        expect(wrapper.find('a').text()).to.equal('current_website');
    });
});
