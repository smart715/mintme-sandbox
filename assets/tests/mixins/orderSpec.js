import Vue from 'vue';
import {shallowMount} from '@vue/test-utils';
import OrderMixin from '../../js/mixins/order';
import {WSAPI} from '../../js/utils/constants';

describe('OrderMixin', function() {
    const $url = 'URL';
    const $routing = {generate: () => $url};
    const Component = Vue.component('foo', {
        mixins: [OrderMixin],
    });
    const wrapper = shallowMount(Component, {
        mocks: {
            $routing,
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
        wrapper.vm.loggedIn = false;
        expect(wrapper.vm.showDepositMoreLink).to.be.false;

        wrapper.vm.loggedIn = true;
        wrapper.vm.action = 'buy';
        expect(wrapper.vm.showDepositMoreLink).to.be.true;

        wrapper.vm.action = 'exchange';
        expect(wrapper.vm.showDepositMoreLink).to.be.false;
    });

    it('should handle order input class for not logged (width 100%) / logged in (width 50%)', () => {
        wrapper.vm.loggedIn = false;
        expect(wrapper.vm.orderInputClass).to.deep.equal('w-100');

        wrapper.vm.loggedIn = true;
        expect(wrapper.vm.orderInputClass).to.deep.equal('w-50');
    });

    it('should handle generate depositMoreLink correctly', () => {
        wrapper.vm.action = 'buy';
        expect(wrapper.vm.depositMoreLink).to.deep.equal($url);

        wrapper.vm.action = 'exchange';
        expect(wrapper.vm.depositMoreLink).to.deep.equal(undefined);

        wrapper.vm.action = 'sell';
        expect(wrapper.vm.depositMoreLink).to.deep.equal($url);
    });

    it('should handle market identifier for buy/sell operations correctly', () => {
        wrapper.vm.action = 'buy';
        expect(wrapper.vm.marketIdentifier).to.deep.equal(wrapper.vm.market.base.identifier);

        wrapper.vm.action = 'exchange';
        expect(wrapper.vm.marketIdentifier).to.deep.equal('');

        wrapper.vm.action = 'sell';
        expect(wrapper.vm.marketIdentifier).to.deep.equal(wrapper.vm.market.quote.identifier);
    });

    it('should handle market check (BTC or WEB) correctly', () => {
        wrapper.vm.action = 'buy';
        expect(wrapper.vm.isMarketBTCOrWEB).to.be.true;

        wrapper.vm.action = 'exchange';
        expect(wrapper.vm.isMarketBTCOrWEB).to.be.false;

        wrapper.vm.action = 'sell';
        expect(wrapper.vm.isMarketBTCOrWEB).to.be.true;
    });

    it('should return correctly order side by type', () => {
        expect(wrapper.vm.getSideByType(WSAPI.order.type.BUY)).equal('Buy');
        expect(wrapper.vm.getSideByType(WSAPI.order.type.SELL)).equal('Sell');
        expect(wrapper.vm.getSideByType(WSAPI.order.type.DONATION)).equal('Donation');
        expect(wrapper.vm.getSideByType(WSAPI.order.type.BUY, true)).equal('Buy (donation)');
        expect(wrapper.vm.getSideByType(WSAPI.order.type.SELL, true)).equal('Sell (donation)');
    });
});
