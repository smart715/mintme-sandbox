import {shallowMount} from '@vue/test-utils';
import LineChart from '../../js/components/UI/charts/LineChart';

// Mock the LineCmp component
const LineCmp = {
    render: () => {},
    props: ['chartData', 'chartOptions', 'plugins'],
};

const chartData = {
    labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
    datasets: [
        {
            label: 'Data One',
            backgroundColor: '#f87979',
            borderColor: '#f87979',
            data: [40, 39, 10, 40, 39, 80, 40],
        },
    ],
};

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
};

const plugins = [];

/**
 * @param {Object} props
 * @return {Object}
 */
function createSharedTestProps(props = {}) {
    return {
        chartData,
        chartOptions,
        plugins,
        ...props,
    };
}

describe('LineChart', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(LineChart, {
            propsData: createSharedTestProps(),
            stubs: {
                LineCmp,
            },
        });
    });

    it('renders a LineCmp component with correct props', () => {
        expect(wrapper.findComponent(LineCmp).exists()).toBe(true);
        expect(wrapper.props('chartData')).toBe(chartData);
        expect(wrapper.props('chartOptions')).toBe(chartOptions);
        expect(wrapper.props('plugins')).toBe(plugins);
    });
});
