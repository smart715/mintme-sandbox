import {createLocalVue} from '@vue/test-utils';
import tableSortPlugin from '../../js/table_sort_plugin.js';

describe('table_sort_plugin', () => {
    it('sorts two different numbers correctly', () => {
        const localVue = createLocalVue();
        localVue.use(tableSortPlugin);
        expect(localVue.prototype.$numericCompare(12.3, 11.1)).toBe(1);
        expect(localVue.prototype.$numericCompare(11.1, 12.3)).toBe(-1);
        expect(localVue.prototype.$numericCompare(12.3, 12.3)).toBe(0);
    });

    it('sorts two different dates correctly', () => {
        const localVue = createLocalVue();
        localVue.use(tableSortPlugin);
        const a = '24.02.2020 15:48:51';
        const b = '03.03.2020 15:33:51';
        expect(localVue.prototype.$dateCompare(a, b)).toBe(-1);
        expect(localVue.prototype.$dateCompare(b, a)).toBe(1);
        expect(localVue.prototype.$dateCompare(a, a)).toBe(0);
    });

    it('should select and sort the correct type based on the key', () => {
        const localVue = createLocalVue();
        localVue.use(tableSortPlugin);
        const fields = {
            date: {
                key: 'date',
                type: 'date',
            },
            type: {
                key: 'type',
                type: 'string',
            },
            name: {
                key: 'name',
                type: 'string',
            },
            amount: {
                key: 'amount',
                type: 'numeric',
            },
            price: {
                key: 'price',
                type: 'numeric',
            },
            total: {
                key: 'total',
                type: 'numeric',
            },
            fee: {key: 'fee', type: 'numeric'},
        };
        Object.keys(fields).forEach((key) => {
            switch (key.type) {
                case 'date':
                    const a = '24.02.2020 15:48:51';
                    const b = '03.03.2020 15:33:51';
                    expect(localVue.prototype.$sortCompare(b, a)).toBe(1);
                    expect(localVue.prototype.$sortCompare(a, b)).toBe(-1);
                    expect(localVue.prototype.$sortCompare(a, a)).toBe(0);
                case 'string':
                    const c = 'en'; const d = 'fr';
                    expect(localVue.prototype.$sortCompare(c, d)).toBe(-1);
                    expect(localVue.prototype.$sortCompare(d, c)).toBe(1);
                    expect(localVue.prototype.$sortCompare(c, c)).toBe(0);
                case 'numeric':
                    const e = 12.3; const f = 11.3;
                    expect(localVue.prototype.$sortCompare(e, f)).toBe(1);
                    expect(localVue.prototype.$sortCompare(f, e)).toBe(-1);
                    expect(localVue.prototype.$sortCompare(e, e)).toBe(0);
            };
        });
    });
});

