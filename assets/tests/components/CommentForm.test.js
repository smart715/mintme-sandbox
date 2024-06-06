import {shallowMount, createLocalVue} from '@vue/test-utils';
import CommentForm from '../../js/components/posts/CommentForm';
import Vuex from 'vuex';
import Vuelidate from 'vuelidate';
import axios from 'axios';
import moxios from 'moxios';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use(Vuelidate);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$toasted = {show: (val) => val};
        },
    });
    return localVue;
}

/**
 * @return {Vuex.Store}
 */
function createSharedTestStore() {
    return new Vuex.Store({
        modules: {
            tradeBalance: {
                namespaced: true,
                getters: {
                    getQuoteBalance: () => 0,
                },
            },
            user: {
                namespaced: true,
                getters: {
                    getHasPhoneVerified: () => true,
                },
            },
        },
    });
}

const testComment = {
    content: 'foo',
    minContentLength: 2,
    maxContentLength: 5,
};

describe('CommentForm', () => {
    beforeEach(() => {
        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('button save is disabled if content length is outside of the min-max range', async () => {
        const localVue = mockVue();

        const wrapper = shallowMount(CommentForm, {
            localVue,
            store: createSharedTestStore,
        });

        await wrapper.setData({...testComment, content: ''});
        expect(wrapper.findComponent({ref: 'commentButton'}).attributes('disabled')).toBe('true');
    });

    it('button save is enabled if withing length range', async () => {
        const localVue = mockVue();

        const wrapper = shallowMount(CommentForm, {
            localVue,
            store: createSharedTestStore,
        });

        await wrapper.setData(testComment);
        expect(wrapper.findComponent({ref: 'commentButton'}).attributes('disabled')).toBe('true');
    });

    it('button save or cancel pressed but user not logged in', async () => {
        const localVue = mockVue();

        global.window = Object.create(window);

        Object.defineProperty(window, 'location', {
            value: {
                href: '',
            },
        });

        const wrapper = shallowMount(CommentForm, {
            localVue,
            propsData: {
                loggedIn: false,
            },
            store: createSharedTestStore,
        });

        await wrapper.setData(testComment);
        wrapper.vm.loginUrl = 'http://dummy.com';

        wrapper.vm.submit();
        expect(window.location.href).toBe('http://dummy.com');

        window.location.href = '';

        wrapper.vm.cancel();
        expect(window.location.href).toBe('http://dummy.com');
    });

    it('button save or cancel pressed and user loggedin with phone verified', async (done) => {
        const localVue = mockVue();

        const wrapper = shallowMount(CommentForm, {
            localVue,
            propsData: {
                loggedIn: true,
                apiUrl: 'http://dummy.com',
            },
            store: createSharedTestStore,
        });

        await wrapper.setData(testComment);

        moxios.stubRequest('http://dummy.com', {status: 200, response: {comment: 'bar'}});

        moxios.wait(() => {
            expect(wrapper.emitted('submitted')[0][0]).toBe('bar');
            done();
        });

        wrapper.vm.submit();

        wrapper.vm.cancel();
        expect(wrapper.emitted('cancel')).toBeTruthy();
    });

    it('button submit pressed but response with error', async (done) => {
        const localVue = mockVue();

        const wrapper = shallowMount(CommentForm, {
            localVue,
            propsData: {
                loggedIn: true,
                apiUrl: 'http://dummy.com',
            },
            store: createSharedTestStore,
        });

        await wrapper.setData(testComment);

        const error = new Error('Error: Request failed with status code 500');
        moxios.stubRequest('http://dummy.com', {status: 500, response: {error}});
        moxios.wait(() => {
            expect(wrapper.emitted('error')).toBeTruthy();
            done();
        });

        wrapper.vm.submit();
    });

    it('focus in textarea and user not loggedin', async () => {
        const localVue = mockVue();

        global.window = Object.create(window);

        Object.defineProperty(window, 'location', {
            value: {
                href: '',
            },
        });

        const wrapper = shallowMount(CommentForm, {
            localVue,
            propsData: {
                loggedIn: false,
                commentMinAmount: 1000,
                post: {
                    token: {
                        name: 'foo',
                    },
                },
            },
            store: createSharedTestStore,
        });

        const event = {
            target: {
                blur() {
                    return;
                },
            },
        };

        await wrapper.setData(testComment);
        wrapper.vm.loginUrl = 'http://dummy.com';

        wrapper.vm.goToLogInIfGuest(event);
        expect(window.location.href).toBe('http://dummy.com');
    });
});
