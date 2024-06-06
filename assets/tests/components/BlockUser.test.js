import {shallowMount, createLocalVue} from '@vue/test-utils';
import BlockUser from '../../js/components/profile/BlockUser.vue';
import {NotificationMixin} from '../../js/mixins';
import axios from 'axios';
import moxios from 'moxios';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.mixin(NotificationMixin);
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$logger = {error: (val, params) => val, success: (val, params) => val};
            Vue.prototype.$toasted = {show: (val) => val};
        },
    });
    return localVue;
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createSharedTestProps(props = {}) {
    return {
        nickname: 'nicknameTest',
        isBlocked: isBlockedProp,
        ...props,
    };
}

const isBlockedProp = false;

describe('BlockUser', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(BlockUser, {
            localVue: localVue,
            propsData: createSharedTestProps(),
            directives: {
                'b-tooltip': {},
            },
        });

        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
        wrapper.destroy();
    });

    it('Verify that "blockMsg" works correctly', async () => {
        await wrapper.setData({
            blocked: isBlockedProp,
        });
        expect(wrapper.vm.blockMsg).toBe('profile.block_user.ban');

        await wrapper.setData({
            blocked: !isBlockedProp,
        });
        expect(wrapper.vm.blockMsg).toBe('profile.block_user.unban');
    });

    it('Verify that "blockTooltipMsg" works correctly', async () => {
        await wrapper.setData({
            blocked: isBlockedProp,
        });
        expect(wrapper.vm.blockTooltipMsg).toBe('profile.block_user.ban.tooltip');

        await wrapper.setData({
            blocked: !isBlockedProp,
        });
        expect(wrapper.vm.blockTooltipMsg).toBe('profile.block_user.unban.tooltip');
    });

    it('Verify that "blockAction" works correctly when "loading" is true', async () => {
        await wrapper.setData({
            isConfirmVisible: false,
            blocked: isBlockedProp,
            loading: true,
        });
        wrapper.vm.blockAction();

        expect(wrapper.vm.isConfirmVisible).toBe(false);
    });

    it('Verify that "blockAction" works correctly when "loading" is false', async () => {
        await wrapper.setData({
            isConfirmVisible: false,
            blocked: isBlockedProp,
            loading: false,
        });
        wrapper.vm.blockAction();

        expect(wrapper.vm.isConfirmVisible).toBe(true);
    });

    describe('Verify that "blockAction" works correctly', () => {
        it('When "blocked" is true', async (done) => {
            await wrapper.setData({
                isConfirmVisible: false,
                blocked: !isBlockedProp,
                loading: false,
            });

            moxios.stubRequest('unblock_profile', {
                status: 200,
                response: {},
            });

            await wrapper.vm.blockAction();

            moxios.wait(() => {
                expect(wrapper.vm.isConfirmVisible).toBe(false);
                expect(wrapper.vm.blocked).toBe(false);
                done();
            });
        });

        it('blockAction denied', async (done) => {
            wrapper.vm.notifyError = jest.fn();

            await wrapper.setData({
                isConfirmVisible: false,
                blocked: !isBlockedProp,
                loading: false,
            });

            moxios.stubRequest('unblock_profile', {
                status: 403,
                response: {
                    message: 'error-message',
                },
            });

            await wrapper.vm.blockAction();

            moxios.wait(() => {
                expect(wrapper.vm.notifyError).toHaveBeenCalledWith('toasted.error.try_reload');
                expect(wrapper.vm.loading).toBe(false);
                done();
            });
        });
    });

    describe('Verify that "blockUser" works correctly', () => {
        it('blockUser', async (done) => {
            moxios.stubRequest('block_profile', {
                status: 200,
                response: {},
            });

            await wrapper.vm.blockUser();

            moxios.wait(() => {
                expect(wrapper.vm.blocked).toBe(true);
                expect(wrapper.vm.loading).toBe(false);
                done();
            });
        });

        it('blockUser denied', async (done) => {
            wrapper.vm.notifyError = jest.fn();

            moxios.stubRequest('block_profile', {
                status: 403,
                response: {
                    message: 'error-message',
                },
            });

            await wrapper.vm.blockUser();

            moxios.wait(() => {
                expect(wrapper.vm.notifyError).toHaveBeenCalledWith('toasted.error.try_reload');
                expect(wrapper.vm.loading).toBe(false);
                done();
            });
        });
    });
});
