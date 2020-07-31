import Vue from 'vue';
import {shallowMount} from '@vue/test-utils';
import OrderHighlightsMixin from '../../js/mixins/order_highlights';

describe('OrderHighlightsMixin', function() {
    const Component = Vue.component('foo', {
        template: '<div></div>',
        mixins: [OrderHighlightsMixin],
    });
    const wrapper = shallowMount(Component);

    it('should highlight (success-highlight) new added order for price 1', () => {
        let orders = [];
        let newOrders = [
            {price: '1', createdTimestamp: Date.now(), maker: {id: 1, profile: {nickname: 'user1'}}},
        ];


        let delay = wrapper.vm.handleOrderHighlights(orders, newOrders);
        expect(delay).toBe(false);
        expect(newOrders[0].highlightClass).toBe('success-highlight');
    });

    it('should highlight (success-highlight) order for price 1 as amount increased', () => {
        let orders = [
            {price: '1', amount: '5', createdTimestamp: Date.now(), maker: {id: 1, profile: {nickname: 'user1'}}},
        ];
        let newOrders = [
            {price: '1', amount: '8.3245', createdTimestamp: Date.now(), maker: {id: 1, profile: {nickname: 'user1'}}},
        ];

        let delay = wrapper.vm.handleOrderHighlights(orders, newOrders);
        expect(delay).toBe(false);
        expect(newOrders[0].highlightClass).toBe('success-highlight');
    });

    it('should highlight (error-highlight) order for price 1 as amount decreased and delay orders updating', () => {
        let orders = [
            {price: '1', amount: '4.7865', createdTimestamp: Date.now(), maker: {id: 1, profile: {nickname: 'user1'}}},
        ];
        let newOrders = [
            {price: '1', amount: '3.1247', createdTimestamp: Date.now(), maker: {id: 1, profile: {nickname: 'user1'}}},
        ];

        let delay = wrapper.vm.handleOrderHighlights(orders, newOrders);
        expect(delay).toBe(true);
        expect(orders[0].highlightClass).toBe('error-highlight');
    });

    it('should highlight (error-highlight) removed orders for price 1', () => {
        let orders = [
            {price: '1', createdTimestamp: Date.now(), maker: {id: 1, profile: {nickname: 'user1'}}},
            {price: '1', createdTimestamp: Date.now(), maker: {id: 1, profile: {nickname: 'user1'}}},
        ];
        let newOrders = [];


        let delay = wrapper.vm.handleOrderHighlights(orders, newOrders);
        expect(delay).toBe(true);
        expect(orders[0].highlightClass).toBe('error-highlight');
    });
});
