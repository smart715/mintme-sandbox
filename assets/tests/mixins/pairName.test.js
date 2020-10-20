import {shallowMount} from '@vue/test-utils';
import pairNameMixin from '../../js/mixins/pair_name';
import Vue from 'vue';

describe('pairNameMixin', () => {
    const component = Vue.component('foo', {
        template: '<div></div>',
        mixins: [pairNameMixin],
    });
    const wrapper = shallowMount(component);

    it('show full pair name if base quote is not MINTME', () => {
        expect(wrapper.vm.pairNameFunc('BTC', 'MINTME')).toBe('MINTME/BTC');
    });

    it('show full pair name if base quote is XMR', () => {
        expect(wrapper.vm.pairNameFunc('XMR', 'MINTME')).toBe('MINTME/XMR');
    });

    it('hide base quote equal to MINTME', () => {
        expect(wrapper.vm.pairNameFunc('MINTME', 'Food')).toBe('Food');
    });

    it('hide base quote equal to WEB', () => {
        expect(wrapper.vm.pairNameFunc('WEB', 'SMTH')).toBe('SMTH');
    });
});
