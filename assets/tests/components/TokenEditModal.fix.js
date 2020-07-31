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

const vueSliderTest = Vue.component('vue-slider-test', {
    template: '<div></div>',
        data() {
            return {
                refreshd: false,
            };
        },
        methods: {
            refresh: function() {
                this.refreshd = true;
            },
    },
});

const vueSliderPeriodTest = Vue.component('vue-slider-period-test', {
    template: '<div></div>',
        data() {
            return {
                refreshd: false,
            };
    },
    methods: {
        refresh: function() {
            this.refreshd = true;
        },
    },
});


const tokenReleasePeriodTest = Vue.component('token-release-period', {
    template: '<div><vue-slider-test ref="released-slider"></vue-slider-test><vue-slider-period-test ref="release-period-slider"></vue-slider-period-test></div>',
    components: {
         vueSliderTest,
         vueSliderPeriodTest,
    },

});

describe('TokenEditModal', () => {
    it('should be true when statusProp props is equal "not-deployed"', () => {
        const wrapper = shallowMount(TokenEditModal, {
            propsData: propsForTestCorrectlyRenders,
        });
        expect(wrapper.vm.isTokenNotDeployed).toBe(true);
    });

    it('should be true when statusProp props is equal "deployed"', () => {
        propsForTestCorrectlyRenders.statusProp = 'deployed';
        const wrapper = shallowMount(TokenEditModal, {
            propsData: propsForTestCorrectlyRenders,
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
            stubs: {
                'token-release-period': tokenReleasePeriodTest,
            },
        });

        wrapper.vm.refreshSliders();
        expect(wrapper.find(vueSliderTest).vm.refreshd).toBe(true);
        expect(wrapper.find(vueSliderPeriodTest).vm.refreshd).toBe(true);
    });
});
