import {shallowMount} from '@vue/test-utils';
import BbcodeEditor from '../../js/components/bbcode/BbcodeEditor';

let fooEvent = {target: {value: 'foo'}};

describe('BbcodeEditor', () => {
    it('should equal to value props', () => {
        const wrapper = shallowMount(BbcodeEditor, {
            propsData: {
                value: 'foo',
            },
        });
        expect(wrapper.vm.newValue).to.be.equal('foo');
    });

    it('should equal to value when the value changes', () => {
        const wrapper = shallowMount(BbcodeEditor, {
            propsData: {
                value: '',
            },
        });
        wrapper.find('textarea').setValue('foo');
        expect(wrapper.vm.newValue).to.be.equal('foo');
    });

    it('should equal to value and emit "change" when the function onChange() is called', () => {
        const wrapper = shallowMount(BbcodeEditor, {
            propsData: {
                value: '',
            },
        });
        wrapper.vm.onChange(fooEvent);
        expect(wrapper.vm.newValue).to.be.equal('foo');
        expect(wrapper.emitted('change').length).to.be.equal(1);
        expect(wrapper.emitted('change')[0][0]).to.be.equal('foo');
    });

    it('should equal to value and emit "input" when the function onInput() is called', () => {
        const wrapper = shallowMount(BbcodeEditor, {
            propsData: {
                value: '',
            },
        });
        wrapper.vm.onInput(fooEvent);
        expect(wrapper.vm.newValue).to.be.equal('foo');
        expect(wrapper.emitted('input').length).to.be.equal(1);
        expect(wrapper.emitted('input')[0][0]).to.be.equal('foo');
    });
});
