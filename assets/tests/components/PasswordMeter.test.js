import {createLocalVue, shallowMount} from '@vue/test-utils';
import PasswordMeter from '../../js/components/PasswordMeter';

jest.requireActual('zxcvbn');

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$t = (val) => val;
        },
    });

    return localVue;
}

describe('PasswordMeter', () => {
    const wrapper = shallowMount(PasswordMeter, {
        localVue: mockVue(),
        propsData: {
            password: 'foo',
        },
    });

    describe('strengthtext', () => {
        it('should equal 1 if password less than 8', () => {
            wrapper.setProps({password: '1'.repeat(7)});
            expect(wrapper.vm.strengthtext).toBe(1);
        });

        it('should equal 2 if password doesn\'t contain (number | uppercase | lowercase)', () => {
            wrapper.setProps({password: 'a'.repeat(8)});
            expect(wrapper.vm.strengthtext).toBe(2);
            wrapper.setProps({password: 'a'.repeat(8) + 'A'});
            expect(wrapper.vm.strengthtext).toBe(2);
            wrapper.setProps({password: '1'.repeat(8) + 'A'});
            expect(wrapper.vm.strengthtext).toBe(2);
            wrapper.setProps({password: '1'.repeat(8) + 'Aa'});
            expect(wrapper.vm.strengthtext).not.toBe(2);
        });

        it('should equal 3 if password length exceed 72 chars and contains (number & uppercase & lowercase)', () => {
            wrapper.setProps({password: '1'.repeat(72) + 'Aa'});
            expect(wrapper.vm.strengthtext).toBe(3);
            wrapper.setProps({password: '1'.repeat(72)});
            expect(wrapper.vm.strengthtext).not.toBe(3);
        });
    });
});
