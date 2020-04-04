import {createLocalVue} from '@vue/test-utils';
import tableSortPlugin from '../../js/table_sort_plugin.js';

it('sorts two different numbers correctly', () => {
    const localVue = createLocalVue();
    localVue.use(tableSortPlugin);
    expect(localVue.prototype.$numericCompare(12.3, 11.1)).to.equal(1);
    expect(localVue.prototype.$numericCompare(11.1, 12.3)).to.equal(-1);
    expect(localVue.prototype.$numericCompare(12.3, 12.3)).to.equal(0);
});

it('sorts two different dates correctly', () => {
    const localVue = createLocalVue();
    localVue.use(tableSortPlugin);
    const a = '24.02.2020 15:48:51';
    const b = '03.03.2020 15:33:51';
    expect(localVue.prototype.$dateCompare(a, b)).to.equal(-1);
    expect(localVue.prototype.$dateCompare(b, a)).to.equal(1);
    expect(localVue.prototype.$dateCompare(a, a)).to.equal(0);
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
    Object.keys(fields).forEach( (key) => {        
        switch(key.type) {
            case 'date':
            const a = '24.02.2020 15:48:51';
            const b = '03.03.2020 15:33:51';
            expect(localVue.prototype.$sortCompare(b, a)).to.equal(1);
            expect(localVue.prototype.$sortCompare(a, b)).to.equal(-1);
            expect(localVue.prototype.$sortCompare(a, a)).to.equal(0);
            case 'string':
            const c = 'en';
            const d = 'fr';
            expect(localVue.prototype.$sortCompare(c, d)).to.equal(-1); 
            expect(localVue.prototype.$sortCompare(d, c)).to.equal(1); 
            expect(localVue.prototype.$sortCompare(c, c)).to.equal(0);
            case 'numeric':
            const e = 12.3; 
            const f = 11.3; 
            expect(localVue.prototype.$sortCompare(e, f)).to.equal(1); 
            expect(localVue.prototype.$sortCompare(f, e)).to.equal(-1); 
            expect(localVue.prototype.$sortCompare(e, e)).to.equal(0);
        };
    });
});

