import {shallowMount} from '@vue/test-utils';
import TokenPageTabSelector from '../../js/mixins/token_tab_selector';
import Vue from 'vue';

const Component = Vue.component('foo', {
    template: '<div></div>',
    mixins: [TokenPageTabSelector],
});

describe('Test if tabIndex its return the data', () => {
    const wrapper = shallowMount(Component);
    it('Should return the name "posts" using tabIndex index', async () => {
        await wrapper.setProps({tabIndex: 1});

        expect(wrapper.vm.currentTabName).toBe('posts');
    });

    it('Should return true if index is 0', async () => {
        await wrapper.setProps({tabIndex: 0});

        expect(wrapper.vm.isIntroTab).toBe(true);
    });

    it('Should return true if index is 2', async () => {
        await wrapper.setProps({tabIndex: 2});

        expect(wrapper.vm.isTradeTab).toBe(true);
    });

    it('Should return true if index is 4', async () => {
        await wrapper.setProps({tabIndex: 4});

        expect(wrapper.vm.isVotingTab).toBe(true);
    });

    it('Should return true if index is 3', async () => {
        await wrapper.setProps({tabIndex: 3});

        expect(wrapper.vm.isPostTab).toBe(true);
    });

    it('Should return true if index 6', async () => {
        await wrapper.setProps({tabIndex: 6});

        expect(wrapper.vm.isShowVotingTab).toBe(true);
    });
});
