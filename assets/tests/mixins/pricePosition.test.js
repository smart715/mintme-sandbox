import Vue from 'vue';
import {shallowMount} from '@vue/test-utils';
import PricePositionMixin from '../../js/mixins/price_position';

describe('pricePosition', function() {
    it('works correctly', () => {
        const Component = Vue.component('foo', {mixins: [PricePositionMixin]});
        const wrapper = shallowMount(Component, {
            propsData: {
                loggedIn: false,
            },
        });

        expect(wrapper.vm.loggedIn).toBe(false);
    });
});
