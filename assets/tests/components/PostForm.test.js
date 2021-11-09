import {shallowMount, createLocalVue} from '@vue/test-utils';
import PostForm from '../../js/components/posts/PostForm';
import Vuelidate from 'vuelidate';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuelidate);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$t = (val) => val;
        },
    });
    return localVue;
}

const testPost = {
    amount: '10',
    content: 'foo',
    title: 'bar',
    shareReward: '1',
};

describe('PostForm', () => {
    it('button is disabled if content, title, amount or shareReward is empty or submitting is true', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(PostForm, {
            localVue,
            propsData: {
                apiUrl: 'testApiUrl',
            },
        });

        wrapper.setData({
            amount: '',
            content: '',
            title: '',
            shareReward: '',
        });

        // all empty
        expect(wrapper.find('button').attributes('disabled')).toBe('disabled');

        // only content is empty
        wrapper.setData({...testPost, content: ''});
        expect(wrapper.find('button').attributes('disabled')).toBe('disabled');

        // only title is empty
        wrapper.setData({...testPost, title: ''});
        expect(wrapper.find('button').attributes('disabled')).toBe('disabled');

        // only amount is empty
        wrapper.setData({...testPost, amount: ''});
        expect(wrapper.find('button').attributes('disabled')).toBe('disabled');

        // only shareReward is empty
        wrapper.setData({...testPost, shareReward: ''});
        expect(wrapper.find('button').attributes('disabled')).toBe('disabled');

        // none is empty are not empty but submitting is true
        wrapper.setData({...testPost, submitting: true});
        expect(wrapper.find('button').attributes('disabled')).toBe('disabled');

        // none is empty and submitting is false
        wrapper.setData({...testPost, submitting: false});
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

    it('title validations work', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(PostForm, {
            localVue,
            propsData: {
                apiUrl: 'testApiUrl',
            },
        });

        wrapper.setData({title: '         '});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.title.required).toBe(true);

        wrapper.setData({title: '1234', maxTitleLength: 3});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.title.maxLength).toBe(true);
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

    it('shareReward validations work', () => {
        const localVue = mockVue();
        const wrapper = shallowMount(PostForm, {
            localVue,
            propsData: {
                apiUrl: 'testApiUrl',
            },
        });

        wrapper.setData({shareReward: ''});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.shareReward.required).toBe(true);

        wrapper.setData({shareReward: 'foo'});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.shareReward.decimal).toBe(true);

        wrapper.setData({shareReward: '1.00000', maxDecimals: 4});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.shareReward.maxDecimals).toBe(true);

        wrapper.setData({shareReward: '-1'});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.content.between).toBe(true);

        wrapper.setData({shareReward: '5', maxShareReward: 4});
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
        expect(wrapper.find('input[name=\'amount\']').element.value.includes('10')).toBe(true);
        expect(wrapper.find('input[name=\'share_reward\']').element.value.includes('1')).toBe(true);
        expect(wrapper.find('input[name=\'title\']').element.value.includes('bar')).toBe(true);
    });
});
