import {shallowMount, createLocalVue} from '@vue/test-utils';
import CommentStatusTip from '../../js/components/posts/CommentStatusTip';
import Vuex from 'vuex';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$t = (val) => val;
        },
    });

    return localVue;
}

/**
 * @param {Object} props
 * @param {Object} data
 * @return {Wrapper}
 */
function mockCommentStatusTip(props = {}, data = {}) {
    const localVue = mockVue();
    return shallowMount(CommentStatusTip, {
        localVue: localVue,
        propsData: {
            isLoggedIn: true,
            userHasDeployedToken: true,
            isTipped: true,
            ...props,
        },
        data() {
            return {
                modalVisible: false,
                ...data,
            };
        },
    });
}

describe('CommentStatusTip', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = mockCommentStatusTip();
    });


    it('renders the button when logged in and has deployed token', async () => {
        await wrapper.setProps({
            isLoggedIn: true,
            userHasDeployedToken: true,
            isTipped: false,
        });

        const button = wrapper.findComponent('button');

        expect(button.exists()).toBe(true);
        expect(button.classes()).not.toContain('btn-disabled');
    });

    it('doesn\'t render the button when not logged in', async () => {
        await wrapper.setProps({
            isLoggedIn: false,
            userHasDeployedToken: true,
            isTipped: false,
        });

        const button = wrapper.findComponent('button');

        expect(button.exists()).toBe(false);
    });

    it('renders the button as disabled when token not deployed', async () => {
        await wrapper.setProps({
            isLoggedIn: true,
            userHasDeployedToken: false,
            isTipped: false,
        });

        const button = wrapper.findComponent('button');

        expect(button.exists()).toBe(true);
        expect(button.classes()).toContain('btn-disabled');
    });

    it('renders the button as disabled when already tipped', async () => {
        await wrapper.setProps({
            isLoggedIn: true,
            userHasDeployedToken: true,
            isTipped: true,
        });

        const button = wrapper.findComponent('button');

        expect(button.exists()).toBe(true);
        expect(button.classes()).toContain('btn-disabled');
    });

    it('displays tooltip content correctly when token not deployed', async () => {
        await wrapper.setProps({
            isLoggedIn: true,
            userHasDeployedToken: false,
            isTipped: false,
        });

        expect(wrapper.vm.tooltipContent).toEqual('comment.status.tips.tip.tooltip.not_deployed');
    });

    it('displays tooltip content correctly when already tipped', async () => {
        await wrapper.setProps({
            isLoggedIn: true,
            userHasDeployedToken: true,
            isTipped: true,
        });

        expect(wrapper.vm.tooltipContent).toEqual('comment.status.tips.tip.tooltip.already_tipped');
    });

    it('displays default tooltip content', async () => {
        await wrapper.setProps({
            isLoggedIn: true,
            userHasDeployedToken: true,
            isTipped: false,
        });

        expect(wrapper.vm.tooltipContent).toEqual('comment.status.tips.tip.tooltip');
    });

    it('emits "tip" event when the conditions are met', async () => {
        await wrapper.setProps({
            isLoggedIn: true,
            userHasDeployedToken: true,
            isTipped: false,
        });

        await wrapper.vm.tip();

        expect(wrapper.emitted('tip')).toBeTruthy();
    });

    it('doesn\'t emit "tip" event when the conditions are not met', async () => {
        await wrapper.setProps({
            isLoggedIn: false,
            userHasDeployedToken: true,
            isTipped: false,
        });

        await wrapper.vm.tip();

        expect(wrapper.emitted('tip')).toBeFalsy();
    });
});
