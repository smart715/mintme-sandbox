import Vue from 'vue';
import {shallowMount} from '@vue/test-utils';
import OrderMixin from '../../js/mixins/order';
import {WSAPI} from '../../js/utils/constants';

describe('OrderMixin', function() {
    const $url = 'URL';
    const $routing = {generate: () => $url};
    const Component = Vue.component('foo', {
        template: '<div></div>',
        mixins: [OrderMixin],
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
        methods: {
            rebrandingFunc: function(val) {
                return val;
            },
        },
    });

    it('should show "Deposit more" link if user logged in', () => {
        wrapper.setProps({loggedIn: false});
        expect(wrapper.vm.showDepositMoreLink).toBe(false);

        wrapper.setProps({loggedIn: true});
        wrapper.vm.action = 'buy';
        expect(wrapper.vm.showDepositMoreLink).toBe(true);

        wrapper.vm.action = 'exchange';
        expect(wrapper.vm.showDepositMoreLink).toBe(false);
    });

    it('should handle order input class for not logged (width 100%) / logged in (width 50%)', () => {
        wrapper.setProps({loggedIn: false});
        expect(wrapper.vm.orderInputClass).toBe('w-100');

        wrapper.setProps({loggedIn: true});
        expect(wrapper.vm.orderInputClass).toBe('w-50');
    });

    it('should handle generate depositMoreLink correctly', () => {
        wrapper.vm.action = 'buy';
        expect(wrapper.vm.depositMoreLink).toBe($url);

        wrapper.vm.action = 'exchange';
        expect(wrapper.vm.depositMoreLink).toBe(undefined);

        wrapper.vm.action = 'sell';
        expect(wrapper.vm.depositMoreLink).toBe($url);
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
