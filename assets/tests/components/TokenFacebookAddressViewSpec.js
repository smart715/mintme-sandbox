import {mount} from '@vue/test-utils';
import TokenFacebookAddressView from '../../js/components/token/facebook/TokenFacebookAddressView';

describe('TokenFacebookAddressView', () => {
    it('show facebook link and button', () => {
        const wrapper = mount(TokenFacebookAddressView, {
            propsData: {address: 'facebook_url'},
        });

        expect(wrapper.findAll('a').at(0).text()).to.equal('facebook_url');
        expect(wrapper.findAll('a').at(1).attributes('href')).to.equal('https://www.facebook.com/sharer/sharer.php?u=facebook_url&src=sdkpreparse');
    });
});
