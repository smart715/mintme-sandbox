import {shallowMount} from '@vue/test-utils';
import PasswordMeter from '../../js/components/PasswordMeter';

describe('PasswordMeter', () => {
    const wrapper = shallowMount(PasswordMeter, {
        propsData: {
            password: 'foo',
        },
    });

    describe('strengthtext', () => {
        it('should equal 1 if password less than 8', () => {
            wrapper.vm.password = '1'.repeat(7);
            expect(wrapper.vm.strengthtext).to.deep.equal(1);
        });

        it('should equal 2 if password doesn\'t contain (number | uppercase | lowercase)', () => {
            wrapper.vm.password = 'a'.repeat(8);
            expect(wrapper.vm.strengthtext).to.deep.equal(2);
            wrapper.vm.password = 'a'.repeat(8) + 'A';
            expect(wrapper.vm.strengthtext).to.deep.equal(2);
            wrapper.vm.password = '1'.repeat(8) + 'A';
            expect(wrapper.vm.strengthtext).to.deep.equal(2);
            wrapper.vm.password = '1'.repeat(8) + 'Aa';
            expect(wrapper.vm.strengthtext).to.not.equal(2);
        });

        it('should equal 3 if password length exceed 72 chars and contains (number & uppercase & lowercase)', () => {
            wrapper.vm.password = '1'.repeat(72) + 'Aa';
            expect(wrapper.vm.strengthtext).to.deep.equal(3);
            wrapper.vm.password = '1'.repeat(72);
            expect(wrapper.vm.strengthtext).to.not.equal(3);
        });
    });
});
