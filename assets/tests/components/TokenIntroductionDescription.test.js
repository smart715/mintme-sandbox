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
            Vue.prototype.$logger = {error: () => {}};
        },
    });
    return localVue;
}

/**
 * @param {object} stubs
 * @param {object} mocks
 * @return {Wrapper<Vue>}
 */
function createWrapper(stubs = {}, mocks = {}) {
    const localVue = mockVue();
    const wrapper = shallowMount(TokenIntroductionDescription, {
        propsData: propsForTestCorrectlyRenders,
        stubs: {
            ...stubs,
        },
        mocks: {
            ...mocks,
        },
        localVue,
    });

    return wrapper;
}

const propsForTestCorrectlyRenders = {
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

    describe('Verify that "toggleDescription" works correctly', () => {
        const wrapper = createWrapper();

        it('when showMore is false', async () => {
            await wrapper.setData({
                showMore: false,
            });

            wrapper.vm.toggleDescription();

            expect(wrapper.vm.showMore).toBe(true);
        });

        it('when showMore is true', async () => {
            await wrapper.setData({
                showMore: true,
            });

            wrapper.vm.toggleDescription();

            expect(wrapper.vm.showMore).toBe(false);
        });
    });

    it('Verify that "cancelEditing" works correctly', async () => {
        const wrapper = createWrapper();

        await wrapper.setData({
            saving: false,
        });

        wrapper.vm.cancelEditing();

        expect(wrapper.vm.editingDescription).toBe(false);
    });

    it('renders correctly with assigned props', () => {
        const wrapper = createWrapper(
            {Guide: {template: '<div><slot name="body"></slot></div>'}},
            {$t: (val) => propsForTestCorrectlyRenders.name + val},
        );

        expect(wrapper.vm.newDescription).toBe('a'.repeat(200));
        expect(wrapper.html()).toContain('a'.repeat(200));
        expect(wrapper.html()).toContain('fooName');
    });

    it('should compute showEditIcon correctly', async () => {
        const wrapper = createWrapper({}, {$t: (val) => val});

        await wrapper.setProps({
            editable: true,
        });

        expect(wrapper.vm.showEditIcon).toBe(true);

        await wrapper.setProps({
            editable: false,
        });

        expect(wrapper.vm.showEditIcon).toBe(false);
    });

    it('should compute newDescriptionHtmlDecode correctly', async () => {
        const wrapper = createWrapper({}, {$t: (val) => val});

        await wrapper.setProps({
            description: '&lt;&gt;',
        });

        expect(wrapper.vm.newDescriptionHtmlDecode).toBe('<>');
    });

    it('should watch for description prop', async () => {
        const wrapper = createWrapper({}, {$t: (val) => val});

        await wrapper.setProps({description: 'foo'});

        expect(wrapper.vm.newDescription).toBe('foo');
    });

    it('should set newDescription and readyToSave correctly when the function onDescriptionChange() is called',
        async () => {
            const wrapper = createWrapper({}, {$t: (val) => val});

            wrapper.vm.noBadWordsValidator = () => true;

            await wrapper.setData({
                readyToSave: false,
            });

            wrapper.vm.onDescriptionChange('foo');

            expect(wrapper.vm.newDescription).toBe('foo');
            expect(wrapper.vm.readyToSave).toBe(true);
        }
    );

    describe('should be false when newDescription data is incorrect', () => {
        const wrapper = createWrapper({}, {$t: (val) => val});

        wrapper.vm.noBadWordsValidator = () => true;

        it('newDescription is empty', async () => {
            await wrapper.setData({
                newDescription: '',
            });

            wrapper.vm.$v.$touch();
            expect(!wrapper.vm.$v.newDescription.$error).toBe(false);
        });

        it('newDescription with a length of one character', async () => {
            await wrapper.setData({
                newDescription: 'f',
            });

            wrapper.vm.$v.$touch();
            expect(!wrapper.vm.$v.newDescription.$error).toBe(false);
        });

        it('newDescription with a length of more than one character', async () => {
            await wrapper.setData({
                newDescription: 'foobar',
            });

            wrapper.vm.$v.$touch();
            expect(!wrapper.vm.$v.newDescription.$error).toBe(false);
        });
    });

    it('should be true when newDescription data is correct', async () => {
        const wrapper = createWrapper({}, {$t: (val) => val});

        wrapper.vm.noBadWordsValidator = () => true;

        await wrapper.setData({
            newDescription: `
                Nam quis nulla. Integer malesuada. In in enim a arcu imperdiet malesuada.
                Sed vel lectus. Donec odio urna, tempus molestie, porttitor ut, iaculis quis, sem. Phasellus rhoncus.
                Aenean id metus id velit
            `,
        });

        wrapper.vm.$v.$touch();
        expect(!wrapper.vm.$v.newDescription.$error).toBe(true);
    });

    it('should process when the function onDescriptionChange() is called and newDescription data is incorrect',
        async () => {
            const wrapper = createWrapper({}, {$t: (val) => val});

            await wrapper.setData({
                newDescription: '',
            });

            wrapper.vm.editDescription();

            expect(wrapper.vm.readyToSave).toBe(false);
            expect(wrapper.vm.saving).toBe(false);
        }
    );

    it(
        'do $axios request when the function onDescriptionChange() is called and when newDescription data is correct',
        async (done) => {
            const wrapper = createWrapper({}, {$t: (val) => val});

            wrapper.vm.noBadWordsValidator = () => true;

            await wrapper.setData({
                editingDescription: true,
                newDescription: 'a'.repeat(201),
            });


            moxios.stubRequest('token_update', {
                status: 200,
                response: {newDescription: 'a'.repeat(202)},
            });

            wrapper.vm.editDescription();

            moxios.wait(() => {
                expect(wrapper.emitted('updated').length).toBe(1);
                expect(wrapper.vm.editingDescription).toBe(false);
                expect(wrapper.vm.newDescription).toBe('a'.repeat(202));
                expect(wrapper.vm.editingDescription).toBe(false);
                done();
            });
        }
    );
});
