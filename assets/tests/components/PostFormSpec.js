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

        expect(wrapper.find('button').attributes('disabled')).to.be.equals('disabled');

        wrapper.setData({content: 'foo', submitting: true});
        expect(wrapper.find('button').attributes('disabled')).to.be.equals('disabled');

        wrapper.setData({content: 'foo', submitting: false});
        expect(wrapper.find('button').attributes('disabled')).to.be.undefined;
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
        expect(!wrapper.vm.$v.content.required).to.deep.equal(true);

        wrapper.setData({content: '[b]foo[/b]'});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.content.required).to.deep.equal(false);

        wrapper.setData({content: '         '});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.content.required).to.deep.equal(true);

        wrapper.setData({content: '[ b ] \n \t   [ / b ]'});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.content.required).to.deep.equal(true);

        wrapper.setData({content: '1234', maxContentLength: 3});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.content.maxLength).to.deep.equal(true);

        wrapper.setData({content: '1', minContentLength: 2});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.content.minLength).to.deep.equal(true);
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
        expect(!wrapper.vm.$v.amount.required).to.deep.equal(true);

        wrapper.setData({amount: 'foo'});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.amount.decimal).to.deep.equal(true);

        wrapper.setData({amount: '1.00000', maxDecimals: 4});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.amount.maxDecimals).to.deep.equal(true);

        wrapper.setData({amount: '-1'});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.content.between).to.deep.equal(true);

        wrapper.setData({amount: '5', maxAmount: 4});
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.content.between).to.deep.equal(true);
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
        expect(wrapper.vm.invalidContent).to.be.false;

        // But its true if content isnt empty
        wrapper.setData({content: '       '});
        wrapper.vm.$v.$touch();
        expect(wrapper.vm.invalidContent).to.be.true;

        wrapper.setData({content: 'foo', contentError: true});
        wrapper.vm.$v.$touch();
        expect(wrapper.vm.invalidContent).to.be.true;
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
        expect(wrapper.vm.invalidAmount).to.be.true;
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

        expect(wrapper.find('bbcode-editor-stub').html()).to.contain('foo');
        expect(wrapper.find('input[name=\'amount\']').html()).to.contain('10');
    });
});
