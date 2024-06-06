import {shallowMount, createLocalVue} from '@vue/test-utils';
import CountedTextarea from '../../js/components/UI/CountedTextarea';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    return createLocalVue();
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createSharedTestProps(props = {}) {
    return {
        value: '',
        name: '',
        label: 'Editor',
        rows: 5,
        disabled: false,
        minLength: null,
        invalid: false,
        labelPointerEvents: false,
        textareaTabIndex: '',
        ...props,
    };
}

describe('CountedTextarea', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(CountedTextarea, {
            localVue: localVue,
            propsData: createSharedTestProps(),
        });
    });

    describe('Check that "isNotEnoughLength" works correctly', () => {
        it('When the minimum length is greater than the value', async () => {
            await wrapper.setData({
                localValue: 'jasmdnrc',
            });

            await wrapper.setProps({
                minLength: 20,
            });

            expect(wrapper.vm.isNotEnoughLength).toBe(true);
        });

        it('When the minimum length is less than the value', async () => {
            await wrapper.setData({
                localValue: 'jasmdnrc',
            });

            await wrapper.setProps({
                minLength: 1,
            });

            expect(wrapper.vm.isNotEnoughLength).toBeFalsy();
        });
    });

    describe('Check that "isNotEmpty" works correctly', () => {
        it('Value with length equal to 0', async () => {
            await wrapper.setData({
                localValue: '',
            });

            expect(wrapper.vm.isNotEmpty).toBeFalsy();
        });

        it('Value with greater length 0', async () => {
            await wrapper.setData({
                localValue: 'jasmdnrc',
            });

            expect(wrapper.vm.isNotEmpty).toBeTruthy();
        });
    });

    it('Verify that the "change" event is emitted correctly', async () => {
        await wrapper.setData({
            localValue: 'jasmdnrc',
        });

        wrapper.vm.onChange();

        expect(wrapper.emitted('change')).toBeTruthy();
        expect(wrapper.emitted('change')[0]).toEqual([wrapper.vm.localValue]);
    });

    it('Verify that the "input" event is emitted correctly', async () => {
        await wrapper.setData({
            localValue: 'jasmdnrc',
        });

        wrapper.vm.onInput();

        expect(wrapper.emitted('input')).toBeTruthy();
        expect(wrapper.emitted('input')[0]).toEqual([wrapper.vm.localValue]);
    });
});
