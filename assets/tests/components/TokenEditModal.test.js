import Vue from 'vue';
import {shallowMount} from '@vue/test-utils';
import TokenEditModal from '../../js/components/modal/TokenEditModal';
Vue.use({
    install(Vue, options) {
        Vue.prototype.$t = (val) => val;
    },
});

let propsForTestCorrectlyRenders = {
    currentName: 'testCurrentName',
    hasReleasePeriodProp: false,
    isOwner: true,
    isTokenExchanged: true,
    isMintmeToken: true,
    noClose: false,
    precision: 0,
    releaseAddress: '',
    statusProp: 'not-deployed',
    twofa: true,
    visible: true,
    websocketUrl: '',
};

const refreshSlidersMock = jest.fn();

const tokenReleasePeriodStub = {
    template: '<div></div>',
    methods: {
        refreshSliders: refreshSlidersMock,
    },
};

describe('TokenEditModal', () => {
    it('should be true when statusProp props is equal "not-deployed"', () => {
        const wrapper = shallowMount(TokenEditModal, {
            propsData: propsForTestCorrectlyRenders,
            mocks: {$t: (val) => val},
        });
        expect(wrapper.vm.isTokenNotDeployed).toBe(true);
    });

    it('should be true when statusProp props is equal "deployed"', () => {
        propsForTestCorrectlyRenders.statusProp = 'deployed';
        const wrapper = shallowMount(TokenEditModal, {
            propsData: propsForTestCorrectlyRenders,
            mocks: {$t: (val) => val},
        });
        expect(wrapper.vm.isTokenDeployed).toBe(true);
    });

    it('should be true when the function releasePeriodUpdated() is called', () => {
        const wrapper = shallowMount(TokenEditModal, {
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.releasePeriodUpdated();
        expect(wrapper.vm.hasReleasePeriod).toBe(true);
    });

    it('should refresh sliders for "released-slider" and "release-period-slider" refs', () => {
        const wrapper = shallowMount(TokenEditModal, {
            propsData: propsForTestCorrectlyRenders,
            stubs: {
                'token-release-period': tokenReleasePeriodStub,
            },
        });

        wrapper.vm.refreshSliders();
        expect(refreshSlidersMock).toHaveBeenCalled();
    });
});
