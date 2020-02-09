import {shallowMount} from '@vue/test-utils';
import pairNameMixin from '../../js/mixins/pair_name';
import Vue from 'vue';

describe('pair_name', () => {
    const component = Vue.component('foo', {mixins: [pairNameMixin]});
    const wrapper = shallowMount(component);

    it('show full pair name if base quote is not MINTME', () => {
        expect(wrapper.vm.pairNameFunc('BTC', 'MINTME')).equal('BTC/MINTME');
    });

    it('show full pair name if base quote is XMR', () => {
        expect(wrapper.vm.pairNameFunc('XMR', 'MINTME')).equal('XMR/MINTME');
    });

    it('hide base quote equal to MINTME', () => {
        expect(wrapper.vm.pairNameFunc('MINTME', 'Food')).equal('Food');
    });

    it('hide base quote equal to WEB', () => {
        expect(wrapper.vm.pairNameFunc('WEB', 'SMTH')).equal('SMTH');
    });
});
