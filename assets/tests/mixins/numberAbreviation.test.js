import numberAbbreviationMixin from '../../js/mixins/filters/numberAbbreviation';
import {shallowMount} from '@vue/test-utils';
import Vue from 'vue';

describe('numberAbbreviationMixin', () => {
    const component = Vue.component('foo', {
        template: '<div></div>',
        mixins: [numberAbbreviationMixin],
    });
    const wrapper = shallowMount(component);

    it('show same value if the value less than 1000', () => {
        expect(wrapper.vm.numberAbbrFunc(100)).toBe(100);
    });

    it('show 1K if value equal 1000', () => {
        expect(wrapper.vm.numberAbbrFunc(1000)).toBe('1K');
    });

    it('show 1.11K if value equal 1111', () => {
        expect(wrapper.vm.numberAbbrFunc(1111)).toBe('1.11K');
    });

    it('show 2.13M if value equal 2134124', () => {
        expect(wrapper.vm.numberAbbrFunc(2134124)).toBe('2.13M');
    });
});
