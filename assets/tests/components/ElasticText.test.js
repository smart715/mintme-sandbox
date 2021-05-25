import {shallowMount, createLocalVue} from '@vue/test-utils';
import ElasticText from '../../js/components/ElasticText';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.component('b-tooltip', {});
    return localVue;
}

describe('ElasticText', () => {
    it('renders props correctly', () => {
        const wrapper = shallowMount(ElasticText, {
            localVue: mockVue(),
            propsData: {
                value: 'foo',
                url: 'bar',
            },
        });

        expect(wrapper.find('a[href="bar"]').exists()).toBe(true);
        expect(wrapper.find('a').text()).toBe('foo');
    });

    it('updateTooltip works correctly', () => {
        const wrapper = shallowMount(ElasticText, {
            localVue: mockVue(),
            propsData: {
                value: 'foo',
                url: 'bar',
            },
        });
        expect(wrapper.vm.disableTooltip).toBe(false);

        wrapper.vm.updateTooltip(false);

        expect(wrapper.vm.disableTooltip).toBe(true);
    });

    describe('computed component', () => {
        it('should render tag a in case url exists', () => {
            const wrapper = shallowMount(ElasticText, {
                localVue: mockVue(),
                propsData: {
                    value: 'foo',
                    url: 'bar',
                },
            });
            expect(wrapper.vm.component).toBe('a');
        });

        it('should render tag span in case url not exists', () => {
            const wrapper = shallowMount(ElasticText, {
                localVue: mockVue(),
                propsData: {
                    value: 'foo',
                },
            });
            expect(wrapper.vm.component).toBe('span');
        });
    });

    describe('img', () => {
        it('should render it in case exists', () => {
            const wrapper = shallowMount(ElasticText, {
                localVue: mockVue(),
                propsData: {
                    value: 'foo',
                    url: 'bar',
                    img: 'baz',
                },
            });

            expect(wrapper.find('img').exists()).toBe(true);
        });

        it('shouldn\'t render it in case not exists', () => {
            const wrapper = shallowMount(ElasticText, {
                localVue: mockVue(),
                propsData: {
                    value: 'foo',
                    url: 'bar',
                },
            });

            expect(wrapper.find('img').exists()).toBe(false);
        });
    });
});
