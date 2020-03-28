import {createLocalVue} from '@vue/test-utils';
import tableSortPlugin from '../../js/table_sort_plugin.js';

it('adds a $sortCompare method to the vue prototype', () => {
    const localVue = createLocalVue();
    localVue.use(tableSortPlugin);
    (typeof(localVue.prototype.$sortCompare)).should.equals('function');
});

it('adds a $numericCompare method to the vue prototype', () => {
    const localVue = createLocalVue();
    localVue.use(tableSortPlugin);
    (typeof(localVue.prototype.$numericCompare)).should.equals('function');
});

it('sorts two different numbers correctly', () => {
    const localVue = createLocalVue();
    localVue.use(tableSortPlugin);
    expect(localVue.prototype.$numericCompare(12.3, 11.1)).to.equal(1);
    expect(localVue.prototype.$numericCompare(11.1, 12.3)).to.equal(-1);
    expect(localVue.prototype.$numericCompare(12.3, 12.3)).to.equal(0);
});

it('adds a $dateCompare method to the vue prototype', () => {
    const localVue = createLocalVue();
    localVue.use(tableSortPlugin);
    (typeof(localVue.prototype.$dateCompare)).should.equals('function');
});


