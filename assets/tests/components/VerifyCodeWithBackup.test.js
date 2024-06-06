import {createLocalVue, shallowMount} from '@vue/test-utils';
import VerifyCodeWithBackup, {BACKUP_CODE_SIZE} from '../../js/components/VerifyCodeWithBackup';
import Vue from 'vue';

const createCommonWrapper = (options) => {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue, options) {
            Vue.prototype.$t = (val) => val;
        },
    });

    return shallowMount(VerifyCodeWithBackup, {localVue, ...options});
};

const verifyCodeStub = {
    template: '<div/>',
    methods: {
        focus: () => '',
    },
};

describe('VerifyCodeWithBackup', () => {
    it('should display backup mode properly', async () => {
        const wrapper = createCommonWrapper({
            stubs: {
                VerifyCode: verifyCodeStub,
            },
        });

        wrapper.vm.toggleBackupMode();
        await Vue.nextTick();

        expect(wrapper.vm.toggleLinkText).toBe('page.login_2fa.backup_link.authentication_code');
        expect(wrapper.findAll('input').length).toBe(1);

        wrapper.vm.toggleBackupMode();
        await Vue.nextTick();

        expect(wrapper.vm.toggleLinkText).toBe('page.login_2fa.backup_link.backup_code');
        expect(wrapper.findAll('input').length).toBe(0);
    });

    it('should trigger event on backup code entered and has correct length', async () => {
        const wrapper = createCommonWrapper({
            stubs: {
                VerifyCode: verifyCodeStub,
            },
        });

        wrapper.vm.toggleBackupMode();
        await Vue.nextTick();

        await wrapper.findComponent('input[type="text"]').setValue('1'.repeat(BACKUP_CODE_SIZE));

        expect(wrapper.emitted()['code-entered']).toBeTruthy();
        expect(wrapper.emitted()['code-entered'][0]).toEqual(['1'.repeat(BACKUP_CODE_SIZE)]);
    });

    it('should not trigger event on backup code entered and has incorrect length', async () => {
        const wrapper = createCommonWrapper({
            stubs: {
                VerifyCode: verifyCodeStub,
            },
        });

        wrapper.vm.toggleBackupMode();
        await Vue.nextTick();

        await wrapper.findComponent('input[type="text"]').setValue('1'.repeat(BACKUP_CODE_SIZE - 1));
        await wrapper.findComponent('input[type="text"]').setValue('1'.repeat(BACKUP_CODE_SIZE + 1));

        expect(wrapper.emitted()['code-entered']).toBeFalsy();
    });
});
