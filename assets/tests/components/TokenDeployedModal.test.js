import {shallowMount, createLocalVue} from '@vue/test-utils';
import TokenDeployedModal from '../../js/components/modal/TokenDeployedModal.vue';

const tokenNameTest = 'TokenNameTest';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue) {
            Vue.prototype.$t = (val) => val;
        },
    });
    return localVue;
}

/**
 * @param {Object} props
 * @return {Wrapper<Vue>}
 */
function mockTokenDeployedModal(props = {}) {
    return shallowMount(TokenDeployedModal, {
        localVue: mockVue(),
        propsData: {
            visible: false,
            tokenName: tokenNameTest,
            ...props,
        },
    });
}

describe('TokenDeployedModal', () => {
    it('shouldn\'t be visible when visible props is false', () => {
        const wrapper = mockTokenDeployedModal();

        expect(wrapper.findComponent('modal-stub').attributes('visible')).toBe(undefined);
    });

    it('Should be visible when visible props is true', async () => {
        const wrapper = mockTokenDeployedModal();

        await wrapper.setProps({visible: true});
        expect(wrapper.findComponent('modal-stub').attributes('visible')).toBe('true');
    });
});
