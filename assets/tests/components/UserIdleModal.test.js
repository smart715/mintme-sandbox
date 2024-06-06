import {shallowMount, createLocalVue} from '@vue/test-utils';
import vueTabevents from 'vue-tabevents';
import UserIdleModal from '../../js/components/modal/UserIdleModal.vue';
import axios from 'axios';

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(vueTabevents);
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: jest.fn((val) => val)};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$logger = {error: () => {}};
        },
    });
    return localVue;
}

/**
 * @param {Object} props
 * @return {Wrapper<Vue>}
 */
function mockUserIdleModal(props = {}) {
    return shallowMount(UserIdleModal, {
        localVue: mockVue(),
        propsData: {
            timerDuration: '10800',
            modalDuration: '60',
            ...props,
        },
    });
}

describe('UserIdleModal', () => {
    it('shouldn\'t be visible when visible props is false', async () => {
        const wrapper = mockUserIdleModal();

        await wrapper.setData({showModal: false});

        expect(wrapper.findComponent('modal-stub').attributes('visible')).toBe(undefined);
    });

    it('Should be visible when visible props is true', async () => {
        const wrapper = mockUserIdleModal();

        await wrapper.setData({showModal: true});
        expect(wrapper.findComponent('modal-stub').attributes('visible')).toBe('true');
    });

    it('Verify that "openModal" works correctly', () => {
        const wrapper = mockUserIdleModal();

        expect(wrapper.vm.showModal).toBe(false);

        wrapper.vm.openModal();
        expect(wrapper.vm.showModal).toBe(true);
    });

    it('Verify that "closeModal" works correctly', () => {
        const wrapper = mockUserIdleModal();

        wrapper.vm.closeModal();

        expect(wrapper.vm.showModal).toBe(false);
    });

    describe('userIsIdle', () => {
        it('Prompts function if showModal is true', async () => {
            const wrapper = mockUserIdleModal({});
            const openModalSpy = jest.spyOn(wrapper.vm, 'openModal');

            await wrapper.setData({showModal: true});

            wrapper.vm.userIsIdle();

            expect(openModalSpy).not.toHaveBeenCalled();
        });

        it('Calls openModal method if showModal is false', async () => {
            const wrapper = mockUserIdleModal({});
            const openModalSpy = jest.spyOn(wrapper.vm, 'openModal');

            await wrapper.setData({showModal: false});

            wrapper.vm.userIsIdle();

            expect(openModalSpy).toHaveBeenCalled();
        });

        it('Sets extendedSession to false if it\'s true', async () => {
            jest.useFakeTimers();
            const wrapper = mockUserIdleModal();

            await wrapper.setProps({modalDuration: '5'});

            await wrapper.setData({
                showModal: false,
                extendedSession: true,
            });

            await wrapper.vm.userIsIdle();
            jest.advanceTimersByTime(5000);

            expect(wrapper.vm.extendedSession).toBe(false);
            jest.clearAllTimers();
        });
    });

    describe('emitUpdateLastActivity', () => {
        it('Calls updateLastActivity method', () => {
            const wrapper = mockUserIdleModal({});
            const updateLastActivitySpy = jest.spyOn(wrapper.vm, 'updateLastActivity');

            wrapper.vm.emitUpdateLastActivity();

            expect(updateLastActivitySpy).toHaveBeenCalled();
        });
    });

    describe('emitExtendSession', () => {
        it('Calls extendSession method', () => {
            const wrapper = mockUserIdleModal({});
            const closeModalSpy = jest.spyOn(wrapper.vm, 'closeModal');
            const spyExtendSession = jest.spyOn(wrapper.vm, 'extendSession');

            wrapper.vm.emitExtendSession();

            expect(spyExtendSession).toHaveBeenCalled();
            expect(wrapper.vm.extendedSession).toBe(true);
            expect(closeModalSpy).toHaveBeenCalled();
        });
    });

    describe('emitCloseSession', () => {
        it('Calls closeSession method', async () => {
            const wrapper = mockUserIdleModal({});
            const closeSessionSpy = jest.spyOn(wrapper.vm, 'closeSession');
            const spyCloseSession = jest.spyOn(wrapper.vm, 'closeSession');
            const formElement = document.createElement('form');
            formElement.id = 'logout-form';
            document.body.appendChild(formElement);

            await wrapper.setData({
                logOutFormId: 'logout-form',
            });

            await wrapper.vm.emitCloseSession();

            expect(spyCloseSession).toHaveBeenCalled();
            expect(wrapper.vm.loggedOut).toBe(true);
            expect(closeSessionSpy).toHaveBeenCalled();

            formElement.parentNode.removeChild(formElement);
        });
    });

    describe('addAutoLogoutMessage', () => {
        it('adds auto logout message input to form', () => {
            const wrapper = mockUserIdleModal();
            const fakeForm = document.createElement('form');

            fakeForm.id = wrapper.vm.logOutFormId;
            document.body.appendChild(fakeForm);

            wrapper.vm.$t = jest.fn().mockReturnValue('Auto Logout Message');

            wrapper.vm.addAutoLogoutMessage();

            const input = fakeForm.querySelector('#auto-log-out');
            expect(input).not.toBeNull();
            expect(input.getAttribute('type')).toBe('hidden');
            expect(input.getAttribute('name')).toBe('auto_log_out');
            expect(input.value).toBe('Auto Logout Message');

            document.body.removeChild(fakeForm);
        });
    });

    describe('setRedirectionAction', () => {
        it('sets redirection action on the form', () => {
            const wrapper = mockUserIdleModal();
            const fakeForm = document.createElement('form');
            fakeForm.id = wrapper.vm.logOutFormId;
            document.body.appendChild(fakeForm);

            const mockRoutingGenerate = jest.fn().mockReturnValue('auto_logout_redirection');
            wrapper.vm.$routing = {
                generate: mockRoutingGenerate,
            };

            wrapper.vm.setRedirectionAction();

            expect(fakeForm.getAttribute('action')).toBe('auto_logout_redirection');

            document.body.removeChild(fakeForm);
        });
    });

    it('Verify that "updateLastActivity" works correctly', () =>{
        const wrapper = mockUserIdleModal();

        wrapper.vm.updateLastActivity();
        const lastActivityTest = Math.round(new Date() / 1000);

        expect(wrapper.vm.lastActivity).toBe(lastActivityTest);
    });

    it('Verify that "extendSession" works correctly', () =>{
        const wrapper = mockUserIdleModal();

        wrapper.vm.extendSession();

        expect(wrapper.vm.extendedSession).toBe(true);
        expect(wrapper.vm.showModal).toBe(false);
    });

    it('Verify that "idleTime" works correctly', async () =>{
        const wrapper = mockUserIdleModal();

        await wrapper.setData({
            now: 100,
            lastActivity: 22,
        });

        expect(wrapper.vm.idleTime).toBe(78);
    });
});

