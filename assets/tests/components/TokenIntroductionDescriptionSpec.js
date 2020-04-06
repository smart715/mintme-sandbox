import Vue from 'vue';
import Vuelidate from 'vuelidate';
import Toasted from 'vue-toasted';
import {createLocalVue, shallowMount} from '@vue/test-utils';
import TokenIntroductionDescription from '../../js/components/token/introduction/TokenIntroductionDescription';
import moxios from 'moxios';
import axios from 'axios';
Vue.use(Vuelidate);
Vue.use(Toasted);

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(axios);
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
        },
    });
    return localVue;
};

let propsForTestCorrectlyRenders = {
    description: 'fooDescription',
    editable: true,
    name: 'fooName',
 };

describe('TokenIntroductionDescription', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('renders correctly with assigned props', () => {
        const wrapper = shallowMount(TokenIntroductionDescription, {
            propsData: propsForTestCorrectlyRenders,
        });
        expect(wrapper.vm.newDescription).to.be.equal('fooDescription');
        expect(wrapper.html()).to.contain('About your plan:');
        expect(wrapper.html()).to.contain('fooDescription');
        expect(wrapper.html()).to.contain('fooName');
    });

    it('should compute showEditIcon correctly', () => {
        const wrapper = shallowMount(TokenIntroductionDescription, {
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.editable = true;
        expect(wrapper.vm.showEditIcon).to.be.true;

        wrapper.vm.editable = false;
        expect(wrapper.vm.showEditIcon).to.be.false;
    });

    it('should compute newDescriptionHtmlDecode correctly', () => {
        propsForTestCorrectlyRenders.description = '&lt;&gt;';
        const wrapper = shallowMount(TokenIntroductionDescription, {
            propsData: propsForTestCorrectlyRenders,
        });
        propsForTestCorrectlyRenders.description = 'fooDescription';
        expect(wrapper.vm.newDescriptionHtmlDecode).to.be.equal('<>');
    });

    it('should watch for description prop', (done) => {
        const wrapper = shallowMount(TokenIntroductionDescription, {
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.description = 'foo';
        Vue.nextTick(() => {
            expect(wrapper.vm.newDescription).to.be.equal('foo');
            done();
        });
    });

    it('should set newDescription and readyToSave correctly when the function onDescriptionChange() is called', () => {
        const wrapper = shallowMount(TokenIntroductionDescription, {
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.readyToSave = false;
        wrapper.vm.onDescriptionChange('foo');
        expect(wrapper.vm.newDescription).to.be.equal('foo');
        expect(wrapper.vm.readyToSave).to.be.true;
    });

    it('should be false when newDescription data is incorrect', () => {
        const wrapper = shallowMount(TokenIntroductionDescription, {
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.newDescription = '';
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.newDescription.$error).to.be.false;

        wrapper.vm.newDescription = 'f';
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.newDescription.$error).to.be.false;

        wrapper.vm.newDescription = 'foobar';
        wrapper.vm.maxDescriptionLength = 4;
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.newDescription.$error).to.be.false;
    });

    it('should be true when newDescription data is correct', () => {
        const wrapper = shallowMount(TokenIntroductionDescription, {
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.newDescription = 'foobar';
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.newDescription.$error).to.be.true;
    });

    it('should call notifyError() when the function onDescriptionChange() is called and newDescription data is incorrect', () => {
        const wrapper = shallowMount(TokenIntroductionDescription, {
            propsData: propsForTestCorrectlyRenders,
            methods: {
                notifyError: function() {
                    this.$emit('errormessage');
                },
            },
        });
        wrapper.vm.newDescription = 'f';
        wrapper.vm.editDescription();
        expect(wrapper.emitted('errormessage').length).to.be.equal(1);
    });

    it('do $axios request when the function onDescriptionChange() is called and when newDescription data is correct', (done) => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenIntroductionDescription, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
        });
        wrapper.vm.editingDescription = true;
        wrapper.vm.icon = 'foo';
        wrapper.vm.editDescription();

        moxios.stubRequest('token_update', {
            status: 202,
        });

        moxios.wait(() => {
            expect(wrapper.emitted('updated').length).to.be.equal(1);
            expect(wrapper.vm.editingDescription).to.be.false;
            expect(wrapper.vm.icon).to.be.equal('edit');
            done();
        });
    });
});
