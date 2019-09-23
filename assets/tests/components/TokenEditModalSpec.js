import {shallowMount} from '@vue/test-utils';
import TokenEditModal from '../../js/components/modal/TokenEditModal';

describe('TokenEditModal', () => {
    it('renders correctly with assigned props', () => {
        const wrapper = shallowMount(TokenEditModal, {
            propsData: {
                currentName: 'foobar',
                preventAddressEditionProp: false,
            },
        });

        expect(wrapper.find({ref: 'withdrawal-address'}).exists()).to.be.true;
        wrapper.vm.preventAddressEdition = true;
        expect(wrapper.find({ref: 'withdrawal-address'}).exists()).to.be.false;
    });
});
