import Vue from 'vue';
import {shallowMount, createLocalVue} from '@vue/test-utils';
import Vuex from 'vuex';
import makeOrder from '../../js/storage/modules/make_order';
import orderClickedMixin from '../../js/mixins/order_clicked';

describe('orderClickedMixin', function() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    const Component = Vue.component('foo', {
        template: '<div></div>',
        mixins: [orderClickedMixin],
    });

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

        expect(store.getters['makeOrder/getSellPriceInput']).toBe('5');
        expect(store.getters['makeOrder/getBuyPriceInput']).toBe('5');
        expect(store.getters['makeOrder/getSellAmountInput']).toBe('6');
        expect(store.getters['makeOrder/getBuyAmountInput']).toBe('6');
    });

    it('should decrease the amount of the offer if has less balance', () => {
        wrapper.vm.orderClicked({
            price: 5,
            amount: 20,
        });

        expect(store.getters['makeOrder/getSellPriceInput']).toBe('5');
        expect(store.getters['makeOrder/getBuyPriceInput']).toBe('5');
        expect(store.getters['makeOrder/getSellAmountInput']).toBe('12');
        expect(store.getters['makeOrder/getBuyAmountInput']).toBe('10');

        wrapper.vm.orderClicked({
            price: 55,
            amount: 20,
        });

        expect(store.getters['makeOrder/getSellPriceInput']).toBe('55');
        expect(store.getters['makeOrder/getBuyPriceInput']).toBe('55');
        expect(store.getters['makeOrder/getSellAmountInput']).toBe('12');
        expect(store.getters['makeOrder/getBuyAmountInput']).toBe('0.909');
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

        expect(store.getters['makeOrder/getSellPriceInput']).toBe(20);
        expect(store.getters['makeOrder/getBuyPriceInput']).toBe(20);
        expect(store.getters['makeOrder/getSellAmountInput']).toBe('6');
        expect(store.getters['makeOrder/getBuyAmountInput']).toBe('6');
    });
});
