import {shallowMount, createLocalVue} from '@vue/test-utils';
import Feed from '../../js/components/Feed';

Object.defineProperty(window, 'EventSource', {
    value: jest.fn(),
});

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.component('font-awesome-icon', {});
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
        },
    });
    return localVue;
}

const testActivity = {
    type: 0,
    createdAt: '2021-03-02T14:07:06-04:00',
    token: {
        name: 'Foo',
    },
};

describe('Feed', () => {
    it('should compute showFeed correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Feed, {
            localVue,
            propsData: {
                itemsProp: [],
                min: 0,
                max: 1,
            },
        });

        expect(wrapper.vm.showFeed).toBe(false);

        wrapper.setData({items: [testActivity]});

        expect(wrapper.vm.showFeed).toBe(true);
    });

    it('should compute itemsToShow correctly and render rows correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(Feed, {
            localVue,
            propsData: {
                itemsProp: [testActivity],
                min: 6,
                max: 30,
            },
        });

        expect(wrapper.vm.itemsToShow).toBe(1);
        expect(wrapper.findAll('.feed-row').length).toBe(1);

        wrapper.setData({showMore: true});

        expect(wrapper.vm.itemsToShow).toBe(1);
        expect(wrapper.findAll('.feed-row').length).toBe(1);

        wrapper.setData({
            showMore: false,
            items: new Array(7).fill(testActivity, 0, 7),
        });

        expect(wrapper.vm.itemsToShow).toBe(6);
        expect(wrapper.findAll('.feed-row').length).toBe(6);

        wrapper.setData({showMore: true});

        expect(wrapper.vm.itemsToShow).toBe(7);
        expect(wrapper.findAll('.feed-row').length).toBe(7);

        wrapper.setData({
            showMore: false,
            items: new Array(32).fill(testActivity, 0, 32),
        });

        expect(wrapper.vm.itemsToShow).toBe(6);
        expect(wrapper.findAll('.feed-row').length).toBe(6);

        wrapper.setData({showMore: true});

        expect(wrapper.vm.itemsToShow).toBe(30);
        expect(wrapper.findAll('.feed-row').length).toBe(30);
    });
});
