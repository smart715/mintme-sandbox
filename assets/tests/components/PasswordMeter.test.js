import {createLocalVue, shallowMount} from '@vue/test-utils';
import PasswordMeter from '../../js/components/PasswordMeter';

jest.requireActual('zxcvbn');

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$logger = {error: (val) => {}};
        },
    });

    return localVue;
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createPasswordMeterProps(props = {}) {
    return {
        password: 'foo',
        isForgotPassword: false,
        token: 'TOKENCRIS',
        isResetPassword: false,
        showCurrentPasswordError: false,
        ...props,
    };
};

describe('PasswordMeter', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(PasswordMeter, {
            localVue: localVue,
            propsData: createPasswordMeterProps(),
        });
    });

    afterEach(() => {
        wrapper.destroy();
    });

    it('should equal 1 if password less than 8', async () => {
        await wrapper.setProps({password: '1'.repeat(7)});
        expect(wrapper.vm.strengthText).toBe(1);
    });

    it('should equal 2 if password doesn\'t contain (number | uppercase | lowercase)', async () => {
        await wrapper.setProps({password: 'a'.repeat(8)});
        expect(wrapper.vm.strengthText).toBe(2);

        await wrapper.setProps({password: 'a'.repeat(8) + 'A'});
        expect(wrapper.vm.strengthText).toBe(2);

        await wrapper.setProps({password: '1'.repeat(8) + 'A'});
        expect(wrapper.vm.strengthText).toBe(2);

        await wrapper.setProps({password: '1'.repeat(8) + 'Aa'});
        expect(wrapper.vm.strengthText).not.toBe(2);
    });

    it('should equal 3 if password length exceed 72 chars and contains (number & uppercase & lowercase)', async () => {
        await wrapper.setProps({password: '1'.repeat(72) + 'Aa'});
        expect(wrapper.vm.strengthText).toBe(3);

        await wrapper.setProps({password: '1'.repeat(72)});
        expect(wrapper.vm.strengthText).not.toBe(3);
    });

    it('should return 4 if text have spaces', async () => {
        await wrapper.setProps({password: 'Mintme   1*'});
        expect(wrapper.vm.strengthText).toBe(4);
    });

    it('should set checkingDuplicate to false', async () => {
        await wrapper.setProps({currentPassword: 'foo'});
        expect(wrapper.vm.checkingDuplicate).toBeFalsy();
    });

    it('should set duplicateError to isPasswordDuplicate', async () => {
        await wrapper.setProps({currentPassword: 'foo'});
        expect(wrapper.vm.duplicateError).toBe(wrapper.vm.isPasswordDuplicate);
    });

    it('should set checkingDuplicate to false with showCurrentPasswordError', async () => {
        await wrapper.setProps({showCurrentPasswordError: true});
        expect(wrapper.vm.checkingDuplicate).toBeFalsy();
    });

    it('test paswordEqualToSavedPassword', async () => {
        await wrapper.setProps({password: 'foo', token: 'TOKENCRIS'});

        const spy = jest.spyOn(wrapper.vm, 'isPasswordEqualToSavedPassword');

        await wrapper.vm.passwordEqualToSavedPassword();

        expect(spy).toHaveBeenCalledWith('foo', 'TOKENCRIS');

        spy.mockRestore();
    });
});
