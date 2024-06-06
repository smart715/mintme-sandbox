import {shallowMount, createLocalVue} from '@vue/test-utils';
import Feeds from '../../js/components/Feeds';
import Vuex from 'vuex';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
        },
    });
    return localVue;
}

/**
 * @return {Wrapper<Vue>}
 */
function createWrapper() {
    return shallowMount(Feeds, {localVue: mockVue()});
}

describe('Feeds', () => {
    describe('showMoreText', () => {
        it('should return "Show more" when showMore is false', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({showMore: false});
            expect(wrapper.vm.showMoreText).toBe('page.index.see_more');
        });

        it('should return "Show less" when showMore is true', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({showMore: true});

            expect(wrapper.vm.showMoreText).toBe('page.index.see_less');
        });
    });

    describe('toggle', () => {
        it('should set showMore to false if it\'s true', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({showMore: true});
            wrapper.vm.toggle();

            expect(wrapper.vm.showMore).toBe(false);
        });

        it('should set showMore to true if it\'s false', async () => {
            const wrapper = createWrapper();

            await wrapper.setData({showMore: false});
            wrapper.vm.toggle();

            expect(wrapper.vm.showMore).toBe(true);
        });
    });
});
