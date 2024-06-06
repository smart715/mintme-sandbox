import Vue from 'vue';
import {shallowMount} from '@vue/test-utils';
import {OrdersFillWidthMixin} from '../../js/mixins';

const DummyComponent = Vue.component('foo', {
    template: '<div></div>',
    mixins: [OrdersFillWidthMixin],
});

/**
 * @param {Object} options
 * @return {Wrapper<Vue>}
 */
function mockDummyComponent(options = {}) {
    return shallowMount(
        DummyComponent,
        {
            data: () => {
                return {
                    tableData: [],
                };
            },
            ...options,
        },
    );
}

const testOrder1 = {
    amount: 100,
};
const testOrder2 = {
    amount: 200,
};

describe('OrdersFillWidth', function() {
    const wrapper = mockDummyComponent();

    describe('Computed props', () => {
        it('ordersAmount should be calculated properly', () => {
            wrapper.vm.tableData = [];
            expect(wrapper.vm.ordersAmount).toBe(0);

            wrapper.vm.tableData = [{...testOrder1}];
            expect(wrapper.vm.ordersAmount).toBe(1);

            wrapper.vm.tableData = [{...testOrder1}, {...testOrder2}];
            expect(wrapper.vm.ordersAmount).toBe(2);
        });

        it('totalAmount should be calculated properly', () => {
            wrapper.vm.tableData = [];
            expect(wrapper.vm.totalAmount).toBe(0);

            wrapper.vm.tableData = [{...testOrder1}];
            expect(wrapper.vm.totalAmount).toBe(100);

            wrapper.vm.tableData = [{...testOrder1}, {...testOrder2}];
            expect(wrapper.vm.totalAmount).toBe(300);
        });

        it('averageOrderAmount should be calculated properly', () => {
            wrapper.vm.tableData = [];
            expect(wrapper.vm.averageOrderAmount).toBe(0);

            wrapper.vm.tableData = [{...testOrder1}];
            expect(wrapper.vm.averageOrderAmount).toBe(100);

            wrapper.vm.tableData = [{...testOrder1}, {...testOrder2}];
            expect(wrapper.vm.averageOrderAmount).toBe(150);
        });

        it('ordersWithFillWidth should add fillWidth prop to orders', () => {
            wrapper.vm.tableData = [{...testOrder1}, {...testOrder2}];
            expect(wrapper.vm.ordersWithFillWidth).toStrictEqual(
                [
                    {
                        ...testOrder1,
                        fillWidth: 67,
                    },
                    {
                        ...testOrder2,
                        fillWidth: 100,
                    },
                ]
            );
        });
    });

    describe('Methods', () => {
        it('calcFillWidth should calculate properly', () => {
            const options = {
                computed: {
                    averageOrderAmount: () => 100,
                },
            };
            const localWrapper = mockDummyComponent(options);

            expect(localWrapper.vm.calcFillWidth(10)).toBe(10);
            expect(localWrapper.vm.calcFillWidth(70)).toBe(70);
            expect(localWrapper.vm.calcFillWidth(100)).toBe(100);
            expect(localWrapper.vm.calcFillWidth(150)).toBe(100);
        });

        it('orderFillingStyle should return correct string', () => {
            expect(wrapper.vm.orderFillingStyle(10)).toBe('--orderFillWidth: 10%;');
            expect(wrapper.vm.orderFillingStyle(70)).toBe('--orderFillWidth: 70%;');
            expect(wrapper.vm.orderFillingStyle(100)).toBe('--orderFillWidth: 100%;');
        });
    });
});
