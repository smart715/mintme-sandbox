import {shallowMount} from '@vue/test-utils';
import {OpenPageMixin} from '../../js/mixins';
import Vue from 'vue';

describe('OpenPageMixin', () => {
    const component = Vue.component('foo', {
        template: '<div></div>',
        mixins: [OpenPageMixin],
    });
    const wrapper = shallowMount(component);

    it('Open a link in the same tab using the default value _self', () => {
        expect(wrapper.vm.goToPage('https://www.mintme.com')).toBe(true);
    });

    it('Open a link in new tab using _blank', () => {
        expect(wrapper.vm.goToPage('https://www.mintme.com', '_blank')).toBe(true);
    });

    it('Opens the linked document in the parent frame using _parent', () => {
        expect(wrapper.vm.goToPage('https://www.mintme.com', '_parent')).toBe(true);
    });

    it('Opens the linked document in the full body of the window using _top', () => {
        expect(wrapper.vm.goToPage('https://www.mintme.com', '_top')).toBe(true);
    });

    it('using wrong target value', () => {
        expect(wrapper.vm.goToPage('https://www.mintme.com', 'none')).toBe(false);
    });
});
