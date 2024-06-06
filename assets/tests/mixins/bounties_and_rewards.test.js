import {shallowMount, createLocalVue} from '@vue/test-utils';
import BountiesAndRewards from '../../js/mixins/bounties_and_rewards';
import Vue from 'vue';

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
function mockContentEditableTextarea() {
    const Component = Vue.component('foo', {
        mixins: [BountiesAndRewards],
        template: '<div></div>',
    });

    return shallowMount(Component, {
        localVue: mockVue(),
    });
}

describe('BountiesAndRewards', () => {
    let wrapper;

    beforeEach(() => {
        wrapper = mockContentEditableTextarea();
    });

    describe('isItemTruncated', () => {
        it('should return true when item is truncated', () => {
            const parentElementMock = {offsetWidth: 100};
            const childElementMock = {
                offsetWidth: 95,
                parentElement: parentElementMock,
            };

            expect(wrapper.vm.isItemTruncated(childElementMock)).toBe(true);
        });

        it('should return false when item is not truncated', () => {
            const parentElementMock = {offsetWidth: 100};
            const childElementMock = {
                offsetWidth: 90,
                parentElement: parentElementMock,
            };

            expect(wrapper.vm.isItemTruncated(childElementMock)).toBe(false);
        });
    });

    describe('openFinalizeOrSummaryModal', () => {
        it('should redirect to setting page with summary modal when isSettingPage is false and isOwner is true', () => {
            const reward = {};
            const event = {preventDefault: () => {}};
            wrapper.vm.redirectToSettingPageWithSummaryModal = jest.fn();
            const redirectToSettingPageSpy = jest.spyOn(wrapper.vm, 'redirectToSettingPageWithSummaryModal');
            wrapper.vm.isSettingPage = false;
            wrapper.vm.isOwner = true;

            wrapper.vm.openFinalizeOrSummaryModal(reward, event);

            expect(redirectToSettingPageSpy).toHaveBeenCalledWith(reward);
        });

        it('should emit on-summary when isSettingPage is true', () => {
            const reward = {};
            const event = {preventDefault: () => {}};
            wrapper.vm.isSettingPage = true;
            wrapper.vm.isOwner = false;
            wrapper.vm.$emit = jest.fn();

            wrapper.vm.openFinalizeOrSummaryModal(reward, event);

            expect(wrapper.vm.$emit).toHaveBeenCalledWith('on-summary', reward);
        });

        it('should emit open-finalize-modal when isSettingPage is false and isOwner is false', () => {
            const reward = {};
            const event = {preventDefault: () => {}};
            wrapper.vm.isSettingPage = false;
            wrapper.vm.isOwner = false;
            wrapper.vm.$emit = jest.fn();

            wrapper.vm.openFinalizeOrSummaryModal(reward, event);

            expect(wrapper.vm.$emit).toHaveBeenCalledWith('open-finalize-modal', reward);
        });
    });

    describe('redirectToSettingPageWithSummaryModal', () => {
        it('should redirect to setting page', () => {
            const reward = {
                slug: 'slug',
                type: 'type',
            };
            Object.defineProperty(window, 'location', {
                value: {
                    href: '',
                },
            });

            wrapper.vm.redirectToSettingPageWithSummaryModal(reward);

            expect(window.location.href).toBe('token_settings');
        });
    });
});
