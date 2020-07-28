import {shallowMount, createLocalVue} from '@vue/test-utils';
import PostForm from '../../js/components/posts/PostForm';
import Vuelidate from 'vuelidate';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuelidate);
    return localVue;
}

const testPost = {
    amount: '10',
    content: 'foo',
};

describe('PostForm', () => {
    it('button is disabled if content is empty or submitting is true', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(PostForm, {
            localVue,
            propsData: {
                apiUrl: 'testApiUrl',
            },
        });

        expect(wrapper.find('button').attributes('disabled')).toBe('disabled');

        wrapper.setData({content: 'foo', submitting: true});
        expect(wrapper.find('button').attributes('disabled')).toBe('disabled');

        wrapper.setData({content: 'foo', submitting: false});
        expect(wrapper.find('button').attributes('disabled')).toBe(undefined);
    });

    it('content validations work', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(PostForm, {
            localVue,
            propsData: {
                apiUrl: 'testApiUrl',
            },
        });

        wrapper.setData({content: '[b][/b]'});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.content.required).toBe(true);

        wrapper.setData({content: '[b]foo[/b]'});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.content.required).toBe(false);

        wrapper.setData({content: '         '});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.content.required).toBe(true);

        wrapper.setData({content: '[ b ] \n \t   [ / b ]'});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.content.required).toBe(true);

        wrapper.setData({content: '1234', maxContentLength: 3});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.content.maxLength).toBe(true);

        wrapper.setData({content: '1', minContentLength: 2});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.content.minLength).toBe(true);
    });

    it('amount validations work', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(PostForm, {
            localVue,
            propsData: {
                apiUrl: 'testApiUrl',
            },
        });

        wrapper.setData({amount: ''});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.amount.required).toBe(true);

        wrapper.setData({amount: 'foo'});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.amount.decimal).toBe(true);

        wrapper.setData({amount: '1.00000', maxDecimals: 4});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.amount.maxDecimals).toBe(true);

        wrapper.setData({amount: '-1'});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.content.between).toBe(true);

        wrapper.setData({amount: '5', maxAmount: 4});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.content.between).toBe(true);
    });

    it('computes invalidContent correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(PostForm, {
            localVue,
            propsData: {
                apiUrl: 'testApiUrl',
            },
        });

        // it is false if content is empty even if validation fails
        wrapper.vm.$v.$touch();
        expect(wrapper.vm.invalidContent).toBe(false);

        // But its true if content isnt empty
        wrapper.setData({content: '       '});
        wrapper.vm.$v.$touch();
        expect(wrapper.vm.invalidContent).toBe(true);

        wrapper.setData({content: 'foo', contentError: true});
        wrapper.vm.$v.$touch();
        expect(wrapper.vm.invalidContent).toBe(true);
    });

    it('computes invalidAmount correctly', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(PostForm, {
            localVue,
            propsData: {
                apiUrl: 'testApiUrl',
            },
        });

        wrapper.setData({amountError: true});
        wrapper.vm.$v.$touch();
        expect(wrapper.vm.invalidAmount).toBe(true);
    });

    it('displays post if passed', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(PostForm, {
            localVue,
            propsData: {
                apiUrl: 'testApiUrl',
                post: testPost,
            },
        });

        expect(wrapper.find('bbcode-editor-stub').html().includes('foo')).toBe(true);
        expect(wrapper.find('input[name=\'amount\']').html().includes('10')).toBe(true);
    });
});
