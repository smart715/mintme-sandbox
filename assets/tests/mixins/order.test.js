import Vue from 'vue';
import Vuex from 'vuex';
import {shallowMount} from '@vue/test-utils';
import OrderMixin from '../../js/mixins/order';
import {
    WSAPI,
    tokenDeploymentStatus,
} from '../../js/utils/constants';

Vue.use(Vuex);

describe('OrderMixin', function() {
    const $url = 'URL';
    const $routing = {generate: () => $url};
    const Component = Vue.component('foo', {
        template: '<div></div>',
        mixins: [OrderMixin],
        store: new Vuex.Store({
            modules: {
                tokenInfo: {
                    namespaced: true,
                    getters: {getDeploymentStatus: () => true},
                },
                crypto: {
                    namespaced: true,
                    getters: {
                        getCryptosMap: () => {
                            return {
                                'BTC': {},
                                'WEB': {},
                                'ETH': {},
                            };
                        },
                    },
                },
            },
        }),
    });
    const wrapper = shallowMount(Component, {
        mocks: {
            $routing,
            $t: (val) => val,
        },
        propsData: {
            loggedIn: false,
            market: {
                base: {
                    identifier: 'BTC',
                },
                quote: {
                    identifier: 'WEB',
                },
            },
        },
    });

    it('should show "Deposit more" link if user logged in', async () => {
        await wrapper.setProps({loggedIn: false});
        expect(wrapper.vm.showDepositMoreLink).toBe(false);

        await wrapper.setProps({loggedIn: true});
        tokenDeploymentStatus.deployed = true;
        wrapper.vm.action = 'buy';
        expect(wrapper.vm.showDepositMoreLink).toBe(true);

        wrapper.vm.action = 'exchange';
        expect(wrapper.vm.showDepositMoreLink).toBe(true);
    });

    it('should handle market identifier for buy/sell operations correctly', () => {
        wrapper.vm.action = 'buy';
        expect(wrapper.vm.marketIdentifier).toBe(wrapper.vm.market.base.identifier);

        wrapper.vm.action = 'exchange';
        expect(wrapper.vm.marketIdentifier).toBe('');

        wrapper.vm.action = 'sell';
        expect(wrapper.vm.marketIdentifier).toBe(wrapper.vm.market.quote.identifier);
    });

    it('should handle market check (BTC or WEB or ETH) correctly', () => {
        wrapper.vm.action = 'buy';
        expect(wrapper.vm.isCryptoMarket).toBe(true);

        wrapper.vm.action = 'exchange';
        expect(wrapper.vm.isCryptoMarket).toBe(false);

        wrapper.vm.action = 'sell';
        expect(wrapper.vm.isCryptoMarket).toBe(true);
    });

    it('should return correctly order side by type', () => {
        expect(wrapper.vm.getSideByType(WSAPI.order.type.BUY)).toBe('buy');
        expect(wrapper.vm.getSideByType(WSAPI.order.type.SELL)).toBe('sell');
        expect(wrapper.vm.getSideByType(WSAPI.order.type.DONATION)).toBe('donation.order.donation');
        expect(wrapper.vm.getSideByType(WSAPI.order.type.BUY, true)).toBe('donation.order.buy');
        expect(wrapper.vm.getSideByType(WSAPI.order.type.SELL, true)).toBe('donation.order.sell');
    });
});
