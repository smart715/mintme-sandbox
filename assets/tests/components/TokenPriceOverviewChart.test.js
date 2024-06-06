import {createLocalVue, shallowMount} from '@vue/test-utils';
import TokenPriceOverviewChart from '../../js/components/token/TokenPriceOverviewChart.vue';
import moxios from 'moxios';
import axios from 'axios';

/** @return {Wrapper<Vue>}*/
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$toasted = {show: (val) => val};
            Vue.prototype.$logger = {error: (val) => val};
            Vue.prototype.moment = {error: (val) => val};
        },
    });

    return localVue;
}

const DAY = 86400;

describe('TokenPriceOverviewChart', () => {
    let wrapper;

    beforeEach(() => {
        // fixate current date so moment() returns the same date in every test
        Date.now = jest.fn(() => new Date('2020-05-13'));
        moxios.install();
        wrapper = shallowMount(TokenPriceOverviewChart, {
            propsData: {
                currentMarket: {
                    base: {symbol: 'BTC'},
                    quote: {symbol: 'USD'},
                },
            },
            localVue: mockVue(),
        });
    });

    afterEach(() => {
        wrapper.destroy();
        moxios.uninstall();
    });


    it('should set period label correctly', () => {
        const testCases = [
            {
                period: 'week',
                expectedLabels: ['07.05', '08.05', '09.05', '10.05', '11.05', '12.05', '13.05'],
            },
            {
                period: 'month',
                expectedLabels: ['18.04', '23.04', '28.04', '03.05', '08.05', '13.05'],
            },
            {
                period: 'half_year',
                expectedLabels: ['Dec', 'Jan', 'Feb', 'Mar', 'Apr', 'May'],
            },
        ];

        testCases.forEach((testCase) => {
            wrapper.vm.activePeriod = testCase.period;
            wrapper.vm.setPeriodLabel();
            expect(wrapper.vm.chartLabels).toEqual(testCase.expectedLabels);
        });
    });

    it('should set period stats correctly', () => {
        const createTestCase = ({description, period, daysLength, expected}) => ({
            description,
            period,
            statData: Array.from({length: daysLength}, (_, index) => ({
                time: (new Date('2020-05-13')).getTime() / 1000 - index * DAY, // 5/13, 5/12, 5/11 .....
                close: index, // 0, 1, 2, 3, ....
            })),
            expected,
        });

        const testCases = [
            createTestCase({
                description: 'should return correct stats for week',
                period: 'week',
                daysLength: 8, // backend return period + current day if it has enough data
                expected: [6, 5, 4, 3, 2, 1, 0],
            }),
            createTestCase({
                description: 'should return correct stats for month',
                period: 'month',
                daysLength: 31,
                expected: [27, 22, 17, 12, 7, 2],
            }),
            createTestCase({
                description: 'should return correct stats for half_year',
                period: 'half_year',
                daysLength: 181,
                expected: [149, 118, 88, 58, 27.5, 6],
            }),
            createTestCase({
                description: 'should return array of zeros if no data is provided for week',
                period: 'week',
                daysLength: 0,
                expected: [0, 0, 0, 0, 0, 0, 0],
            }),
            createTestCase({
                description: 'should return array of zeros if no data is provided for month',
                period: 'month',
                daysLength: 0,
                expected: [0, 0, 0, 0, 0, 0],
            }),
            createTestCase({
                description: 'should return array of zeros if no data is provided for half_year',
                period: 'half_year',
                daysLength: 0,
                expected: [0, 0, 0, 0, 0, 0],
            }),
            createTestCase({
                description: 'should deal with extra data correctly for week',
                period: 'week',
                daysLength: 25,
                expected: [6, 5, 4, 3, 2, 1, 0],
            }),
            createTestCase({
                description: 'should deal with extra data correctly for month',
                period: 'month',
                daysLength: 80,
                expected: [27, 22, 17, 12, 7, 2],
            }),
            createTestCase({
                description: 'should deal with extra data correctly for half_year',
                period: 'half_year',
                daysLength: 300,
                expected: [149, 118, 88, 58, 27.5, 6],
            }),
            createTestCase({
                description: 'should deal with missing data correctly for week',
                period: 'week',
                daysLength: 5,
                expected: [0, 0, 4, 3, 2, 1, 0],
            }),
            createTestCase({
                description: 'should deal with missing data correctly for month',
                period: 'month',
                daysLength: 20,
                expected: [0, 0, 17, 12, 7, 2],
            }),
            createTestCase({
                description: 'should deal with missing data correctly for half_year',
                period: 'half_year',
                daysLength: 100,
                expected: [0, 0, 86.5, 58, 27.5, 6],
            }),
        ];

        testCases.forEach((testCase) => {
            const {description, period, statData, expected} = testCase;
            wrapper.vm.activePeriod = period;
            wrapper.vm.setPeriodStats(statData);

            const equalArrays = (a, b) => JSON.stringify(a) === JSON.stringify(b);
            equalArrays(wrapper.vm.chartStats, expected)
                ? expect(wrapper.vm.chartStats).toEqual(expected)
                : fail(`${description}\n expected: ${expected}\n got: ${wrapper.vm.chartStats}`);
        });
    });
});
