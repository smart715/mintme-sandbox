import Vue from 'vue';
import Vuelidate from 'vuelidate';
import Toasted from 'vue-toasted';
import {createLocalVue, shallowMount} from '@vue/test-utils';
import TokenIntroductionDescription from '../../js/components/token/introduction/TokenIntroductionDescription';
import moxios from 'moxios';
import '../__mocks__/ResizeObserver';
import axios from 'axios';
Vue.use(Vuelidate);
Vue.use(Toasted);

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$toasted = {show: (val) => val};
        },
    });
    return localVue;
}

let propsForTestCorrectlyRenders = {
    description: 'a'.repeat(200),
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
            stubs: {
                Guide: {template: '<div><slot name="body"></slot></div>'},
            },
            mocks: {$t: (val) => propsForTestCorrectlyRenders.name + val},
        });
        expect(wrapper.vm.newDescription).toBe('a'.repeat(200));
        expect(wrapper.html()).toContain('token.intro.description.header');
        expect(wrapper.html()).toContain('a'.repeat(200));
        expect(wrapper.html()).toContain('fooName');
    });

    it('should compute showEditIcon correctly', () => {
        const wrapper = shallowMount(TokenIntroductionDescription, {
            propsData: propsForTestCorrectlyRenders,
            mocks: {$t: (val) => val},
        });
        wrapper.setProps({editable: true});
        expect(wrapper.vm.showEditIcon).toBe(true);

        wrapper.setProps({editable: false});
        expect(wrapper.vm.showEditIcon).toBe(false);
    });

    it('should compute newDescriptionHtmlDecode correctly', () => {
        propsForTestCorrectlyRenders.description = '&lt;&gt;';
        const wrapper = shallowMount(TokenIntroductionDescription, {
            propsData: propsForTestCorrectlyRenders,
            mocks: {$t: (val) => val},
        });
        propsForTestCorrectlyRenders.description = 'fooDescription';
        expect(wrapper.vm.newDescriptionHtmlDecode).toBe('<>');
    });

    it('should watch for description prop', (done) => {
        const wrapper = shallowMount(TokenIntroductionDescription, {
            propsData: propsForTestCorrectlyRenders,
            mocks: {$t: (val) => val},
        });
        wrapper.setProps({description: 'foo'});
        Vue.nextTick(() => {
            expect(wrapper.vm.newDescription).toBe('foo');
            done();
        });
    });

    it('should set newDescription and readyToSave correctly when the function onDescriptionChange() is called', () => {
        const wrapper = shallowMount(TokenIntroductionDescription, {
            propsData: propsForTestCorrectlyRenders,
            mocks: {$t: (val) => val},
        });
        wrapper.vm.readyToSave = false;
        wrapper.vm.onDescriptionChange('foo');
        expect(wrapper.vm.newDescription).toBe('foo');
        expect(wrapper.vm.readyToSave).toBe(true);
    });

    it('should be false when newDescription data is incorrect', () => {
        const wrapper = shallowMount(TokenIntroductionDescription, {
            propsData: propsForTestCorrectlyRenders,
            mocks: {$t: (val) => val},
        });
        wrapper.vm.newDescription = '';
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.newDescription.$error).toBe(false);

        wrapper.vm.newDescription = 'f';
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.newDescription.$error).toBe(false);

        wrapper.vm.newDescription = 'foobar';
        wrapper.vm.maxDescriptionLength = 4;
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.newDescription.$error).toBe(false);
    });

    it('should be true when newDescription data is correct', () => {
        const wrapper = shallowMount(TokenIntroductionDescription, {
            propsData: propsForTestCorrectlyRenders,
            mocks: {$t: (val) => val},
        });
        wrapper.vm.newDescription = 'Nam quis nulla. Integer malesuada. In in enim a arcu imperdiet malesuada. Sed vel lectus. Donec odio urna, tempus molestie, porttitor ut, iaculis quis, sem. Phasellus rhoncus. Aenean id metus id velit';
        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.newDescription.$error).toBe(true);
    });

    it('should call notifyError() when the function onDescriptionChange() is called and newDescription data is incorrect', () => {
        const wrapper = shallowMount(TokenIntroductionDescription, {
            propsData: propsForTestCorrectlyRenders,
            methods: {
                notifyError: function() {
                    this.$emit('errormessage');
                },
            },
            mocks: {$t: (val) => val},
        });
        wrapper.vm.newDescription = 'foobar';
        wrapper.vm.editDescription();

        moxios.stubRequest('token_update', {
            status: 200, responseText: {data: {newDescription: 'f'}},
        });

        expect(wrapper.emitted('errormessage').length).toBe(1);
    });

    it('do $axios request when the function onDescriptionChange() is called and when newDescription data is correct', (done) => {
        const localVue = mockVue();
        const wrapper = shallowMount(TokenIntroductionDescription, {
            localVue,
            propsData: propsForTestCorrectlyRenders,
            mocks: {$t: (val) => val},
        });
        wrapper.vm.editingDescription = true;
        wrapper.vm.icon = 'foo';
        wrapper.vm.newDescription = 'a'.repeat(201);
        wrapper.vm.editDescription();

        moxios.stubRequest('token_update', {
            status: 200,
            response: {newDescription: 'a'.repeat(202)},
        });

        moxios.wait(() => {
            expect(wrapper.emitted('updated').length).toBe(1);
            expect(wrapper.vm.editingDescription).toBe(false);
            expect(wrapper.vm.newDescription).toBe('a'.repeat(202));
            expect(wrapper.vm.icon).toBe('edit');
            done();
        });
    });
});
