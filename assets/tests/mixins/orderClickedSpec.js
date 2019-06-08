import Vue from 'vue';
import {shallowMount, createLocalVue} from '@vue/test-utils';
import Vuex from 'vuex';
import makeOrder from '../../js/storage/modules/make_order';
import orderClickedMixin from '../../js/mixins/order_clicked';

describe('OrderClicked', function() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    const Component = Vue.component('foo', {mixins: [orderClickedMixin]});

    makeOrder.state.baseBalance = 50;
    makeOrder.state.quoteBalance = 12;
    const store = new Vuex.Store({
        modules: {makeOrder},
    });

    const wrapper = shallowMount(Component, {
        store,
        localVue,
        propsData: {
            basePrecision: 8,
            quotePrecision: 4,
            loggedIn: true,
        },
    });

    it('should add all the offer if has greater balance', () => {
        wrapper.vm.orderClicked({
            price: 5,
            amount: 6,
        });

        expect(store.getters['makeOrder/getSellPriceInput']).to.deep.equal('5.00000000');
        expect(store.getters['makeOrder/getBuyPriceInput']).to.deep.equal('5.00000000');
        expect(store.getters['makeOrder/getSellAmountInput']).to.deep.equal('6.0000');
        expect(store.getters['makeOrder/getBuyAmountInput']).to.deep.equal('6.0000');
    });

    it('should decrease the amount of the offer if has less balance', () => {
        wrapper.vm.orderClicked({
            price: 5,
            amount: 20,
        });

        expect(store.getters['makeOrder/getSellPriceInput']).to.deep.equal('5.00000000');
        expect(store.getters['makeOrder/getBuyPriceInput']).to.deep.equal('5.00000000');
        expect(store.getters['makeOrder/getSellAmountInput']).to.deep.equal('12.0000');
        expect(store.getters['makeOrder/getBuyAmountInput']).to.deep.equal('10.0000');

        wrapper.vm.orderClicked({
            price: 55,
            amount: 20,
        });

        expect(store.getters['makeOrder/getSellPriceInput']).to.deep.equal('55.00000000');
        expect(store.getters['makeOrder/getBuyPriceInput']).to.deep.equal('55.00000000');
        expect(store.getters['makeOrder/getSellAmountInput']).to.deep.equal('12.0000');
        expect(store.getters['makeOrder/getBuyAmountInput']).to.deep.equal('0.9090');
    });

    it('should not update the price if marketPrice is selected', () => {
        wrapper.vm.orderClicked({
            price: 5,
            amount: 6,
        });

        store.commit('makeOrder/setSellPriceInput', 20);
        store.commit('makeOrder/setBuyPriceInput', 20);
        store.commit('makeOrder/setUseSellMarketPrice', true);
        store.commit('makeOrder/setUseBuyMarketPrice', true);

        expect(store.getters['makeOrder/getSellPriceInput']).to.deep.equal(20);
        expect(store.getters['makeOrder/getBuyPriceInput']).to.deep.equal(20);
        expect(store.getters['makeOrder/getSellAmountInput']).to.deep.equal('6.0000');
        expect(store.getters['makeOrder/getBuyAmountInput']).to.deep.equal('6.0000');
    });
});
