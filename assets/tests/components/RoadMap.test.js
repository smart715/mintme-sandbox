import {shallowMount, createLocalVue} from '@vue/test-utils';
import RoadMap from '../../js/components/coin/RoadMap.vue';

/**
 * @return {Wrapper<Vue>}
*/
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue) {
            Vue.prototype.$t = (val) => val;
        },
    });

    return localVue;
}

describe('RoadMap', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(RoadMap, {
            localVue: mockVue(),
        });
    });

    it('renders the component', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('renders the correct header text', () => {
        expect(wrapper.findComponent('.section-header').text()).toBe('page.coin.roadmap.header');
    });

    it('renders the correct checkpoint header text', () => {
        expect(wrapper.findAll('.text-primary')).toHaveLength(3);
        expect(wrapper.findAll('.text-primary').at(0).text()).toBe('page.coin.roadmap.check_point.header.1');
        expect(wrapper.findAll('.text-primary').at(1).text()).toBe('page.coin.roadmap.check_point.header.2');
        expect(wrapper.findAll('.text-primary').at(2).text()).toBe('page.coin.roadmap.last_part');
    });

    it('renders the correct checkpoint body text', () => {
        expect(wrapper.findAll('ul')).toHaveLength(2);
        expect(wrapper.findAll('ul').at(0).html()).toContain('page.coin.roadmap.check_point.body.1');
        expect(wrapper.findAll('ul').at(1).html()).toContain('page.coin.roadmap.check_point.body.2');
    });
});
