import {shallowMount, createLocalVue} from '@vue/test-utils';
import Countdown from '../../js/components/Countdown.vue';

const localVue = mockVue();

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

describe('Countdown', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(Countdown, {
            localVue: localVue,
        });
    });

    it('renders countdown items correctly', async () => {
        await wrapper.setProps({
            endDate: '2023-03-11T00:00:00.000Z',
            currentDate: '2023-03-09T00:00:00.000Z',
            disabled: false,
        });

        expect(wrapper.findAll('.countdown-item')).toHaveLength(4);
    });

    it('shows time up message when end date is reached', () => {
        const wrapper = shallowMount(Countdown, {
            localVue: localVue,
            propsData: {
                endDate: '2023-03-09T00:00:00.000Z',
                currentDate: '2023-03-09T00:00:00.000Z',
                disabled: false,
            },
            slots: {
                content: '<span>Time up!</span>',
            },
        });

        expect(wrapper.findComponent('.text-center').text()).toContain('Time up!');
    });

    it('disables countdown when disabled prop is true', async () => {
        await wrapper.setProps({
            endDate: '2023-03-11T00:00:00.000Z',
            currentDate: '2023-03-09T00:00:00.000Z',
            disabled: true,
        });

        expect(wrapper.findComponent('.countdown').exists()).toBe(false);
    });

    it('formats time correctly', () => {
        const wrapper = shallowMount(Countdown, {
            localVue: localVue,
            propsData: {
                endDate: '2023-03-10T12:30:00.000Z',
                currentDate: '2023-03-09T12:30:00.000Z',
                disabled: false,
            },
        });

        expect(wrapper.findComponent('.countdown-item h2').text()).toContain('01');
        expect(wrapper.findAll('.countdown-item h2').at(1).text()).toContain('00');
        expect(wrapper.findAll('.countdown-item h2').at(2).text()).toContain('00');
        expect(wrapper.findAll('.countdown-item h2').at(3).text()).toContain('00');
    });
});
