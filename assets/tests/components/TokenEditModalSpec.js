import {shallowMount} from '@vue/test-utils';
import TokenEditModal from '../../js/components/modal/TokenEditModal';

let propsForTestCorrectlyRenders = {
    currentName: 'testCurrentName',
    hasReleasePeriodProp: false,
    isOwner: true,
    isTokenExchanged: true,
    noClose: false,
    precision: 0,
    releaseAddress: '',
    statusProp: 'not-deployed',
    twofa: true,
    visible: true,
    websocketUrl: '',
};

describe('TokenEditModal', () => {
    it('should be visible when visible props is true', () => {
        const wrapper = shallowMount(TokenEditModal, {
            propsData: propsForTestCorrectlyRenders,
        });
        expect(wrapper.vm.visible).to.be.true;
    });

    it('should provide closing on ESC and closing on backdrop click when noClose props is false', () => {
        const wrapper = shallowMount(TokenEditModal, {
            propsData: propsForTestCorrectlyRenders,
        });
        expect(wrapper.vm.noClose).to.be.false;
    });

    it('should be true when statusProp props is equal "not-deployed"', () => {
        const wrapper = shallowMount(TokenEditModal, {
            propsData: propsForTestCorrectlyRenders,
        });
        expect(wrapper.vm.isTokenNotDeployed).to.be.true;
    });

    it('should be true when statusProp props is equal "deployed"', () => {
        propsForTestCorrectlyRenders.statusProp = 'deployed';
        const wrapper = shallowMount(TokenEditModal, {
            propsData: propsForTestCorrectlyRenders,
        });
        expect(wrapper.vm.isTokenDeployed).to.be.true;
    });

    it('should be true when the function releasePeriodUpdated() is running', () => {
        const wrapper = shallowMount(TokenEditModal, {
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.releasePeriodUpdated();
        expect(wrapper.vm.hasReleasePeriod).to.be.true;
    });
});
