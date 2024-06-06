import {shallowMount} from '@vue/test-utils';
import VerifyCode from '../../js/components/VerifyCode';
import ClipboardEvent from '../__mocks__/ClipboardEvent';

const createCommonWrapper = (options) => {
    return shallowMount(VerifyCode, options);
};

describe('VerifyCode', () => {
    it('should has 6 inputs by default', () => {
        const wrapper = createCommonWrapper();

        expect(wrapper.findAll('input').length).toBe(6);
    });

    it('should has amount of inputs from prop', () => {
        const wrapper = createCommonWrapper({
            propsData: {
                codeLength: 8,
            },
        });

        expect(wrapper.findAll('input').length).toBe(8);
    });

    it('inputs should be disabled with prop disabled', () => {
        const wrapper = createCommonWrapper({
            propsData: {
                disabled: true,
            },
        });

        expect(wrapper.findAll('input').wrappers.every((wrp) => wrp.element.disabled)).toBe(true);
    });

    it('should trigger event on paste', async () => {
        const wrapper = createCommonWrapper();

        const firstInput = wrapper.findComponent('input');
        await firstInput.trigger('paste', new ClipboardEvent('123456'));

        expect(wrapper.emitted('code-entered')[0]).toStrictEqual(['123456']);
    });

    it('should not trigger event on wrong paste', () => {
        const wrapper = createCommonWrapper();

        const firstInput = wrapper.findComponent('input');
        firstInput.trigger('paste', new ClipboardEvent('ew2344'));
        expect(wrapper.emitted('code-entered')).toBeFalsy();
    });

    it('should not trigger event on not enough chars paste', () => {
        const wrapper = createCommonWrapper();

        const firstInput = wrapper.findComponent('input');
        firstInput.trigger('paste', new ClipboardEvent('12345'));
        expect(wrapper.emitted('code-entered')).toBeFalsy();
    });

    it('should handle user input correctly', () => {
        const wrapper = createCommonWrapper();

        const firstInput = wrapper.findComponent('input');

        firstInput.element.value = '1';
        firstInput.trigger('input');
        expect(firstInput.element.value).toBe('1');

        firstInput.element.value = 'q';
        firstInput.trigger('input');
        expect(firstInput.element.value).toBe('');
    });

    it('it should trigger event after all inputs are done correctly', async () => {
        const wrapper = createCommonWrapper();

        await wrapper.findAll('input').wrappers.forEach((el) => {
            el.element.value = '1';
            el.trigger('input');
        });

        expect(wrapper.emitted('code-entered')[0]).toStrictEqual(['111111']);
    });

    it('it should not trigger event after all inputs are done incorrectly', () => {
        const wrapper = createCommonWrapper();

        wrapper.findAll('input').wrappers.forEach((el) => {
            el.element.value = 'q';
            el.trigger('input');
        });

        expect(wrapper.emitted('code-entered')).toBeFalsy();
    });

    it('should clear all inputs when clearInput function is called', () => {
        const wrapper = createCommonWrapper();

        wrapper.findAll('input').wrappers.forEach((el) => {
            el.element.value = '1';
        });
        expect(wrapper.findAll('input').wrappers.every((wrp) => '1' === wrp.element.value)).toBe(true);

        wrapper.vm.clearInput();

        expect(wrapper.findAll('input').wrappers.every((wrp) => '' === wrp.element.value)).toBe(true);
    });
});
