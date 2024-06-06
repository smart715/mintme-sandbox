import {shallowMount, createLocalVue} from '@vue/test-utils';
import TokenCreatedModal from '../../js/components/modal/TokenCreatedModal.vue';

const tokenNameTest = 'testTokenName';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue) {
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$t = (val) => val;
        },
    });
    return localVue;
}

/**
 * @param {Object} props
 * @return {Wrapper<Vue>}
 */
function mockTokenCreatedModal(props = {}) {
    return shallowMount(TokenCreatedModal, {
        localVue: mockVue(),
        propsData: {
            visible: false,
            tokenName: tokenNameTest,
            ...props,
        },
    });
}

describe('TokenCreatedModal', () => {
    it('shouldn\'t be visible when visible props is false', () => {
        const wrapper = mockTokenCreatedModal();

        expect(wrapper.findComponent('modal-stub').attributes('visible')).toBe(undefined);
    });

    it('Should be visible when visible props is true', async () => {
        const wrapper = mockTokenCreatedModal();

        await wrapper.setProps({visible: true});
        expect(wrapper.findComponent('modal-stub').attributes('visible')).toBe('true');
    });

    it('check that "translationsContext" returns the correct value', () => {
        const wrapper = mockTokenCreatedModal();
        const value = {tokenName: tokenNameTest};

        expect(wrapper.vm.translationsContext).toEqual(value);
    });

    it('check that "translationsContext" returns the correct value', () => {
        const wrapper = mockTokenCreatedModal();
        const value = {tokenName: tokenNameTest};

        expect(wrapper.vm.translationsContext).toEqual(value);
    });

    it('check that "isFirstStep" returns the correct value', async () => {
        const wrapper = mockTokenCreatedModal();

        await wrapper.setData({
            currentStep: 1,
        });

        expect(wrapper.vm.isFirstStep).toBe(true);
    });

    it('check that "prevClasses" returns the correct value', async () => {
        const wrapper = mockTokenCreatedModal();

        await wrapper.setData({
            currentStep: 1,
        });

        expect(wrapper.vm.prevClasses).toEqual({'col-12 text-center': false, 'd-none': true});

        await wrapper.setData({
            currentStep: 4,
        });

        expect(wrapper.vm.prevClasses).toEqual({'col-12 text-center': true, 'd-none': false});
    });

    it('check that "skipClasses" returns the correct value', async () => {
        const wrapper = mockTokenCreatedModal();

        await wrapper.setData({
            currentStep: 1,
        });

        expect(wrapper.vm.skipClasses).toEqual(['col-6 text-right']);

        await wrapper.setData({
            currentStep: 2,
        });

        expect(wrapper.vm.skipClasses).toEqual(['col-2 text-center']);
    });

    it('check that "nextClasses" returns the correct value', async () => {
        const wrapper = mockTokenCreatedModal();

        await wrapper.setData({
            currentStep: 1,
        });

        expect(wrapper.vm.nextClasses).toEqual(['col-6']);

        await wrapper.setData({
            currentStep: 2,
        });

        expect(wrapper.vm.nextClasses).toEqual(['col-4']);
    });

    it('check that "nextClasses" works correctly', async () => {
        const wrapper = mockTokenCreatedModal();

        await wrapper.setData({
            currentStep: 20,
        });

        wrapper.vm.takeStep(2);

        expect(wrapper.vm.currentStep).toBe(22);
    });
});
