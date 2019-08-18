import Vue from 'vue';
import {shallowMount} from '@vue/test-utils';
import PricePositionMixin from '../../js/mixins/price_position';

describe('pricePosition', function() {
    const Component = Vue.component('foo', {mixins: [PricePositionMixin]});
    const wrapper = shallowMount(Component, {
        propsData: {
            loggedIn: false,
        },
    });

    it('triggers place position class correctly', function() {
        expect(wrapper.vm.marketPricePositionClass).to.deep.equals('text-xl-left');
        wrapper.vm.loggedIn = true;
        expect(wrapper.vm.marketPricePositionClass).to.deep.equals('text-sm-right text-xl-right');
    });
});
