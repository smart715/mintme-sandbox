import {shallowMount} from '@vue/test-utils';
import TokenEditModal from '../../js/components/modal/TokenEditModal';

describe('TokenEditModal', () => {
    it('renders correctly with assigned props', () => {
        const wrapper = shallowMount(TokenEditModal, {
            propsData: {
                currentName: 'foobar',
                minDestinationLockedProp: false,
            },
        });

        expect(wrapper.find({ref: 'withdrawal-address'}).exists()).to.be.true;
        wrapper.vm.minDestinationLocked = true;
        expect(wrapper.find({ref: 'withdrawal-address'}).exists()).to.be.false;
    });
});
