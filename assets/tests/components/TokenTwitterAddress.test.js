import {createLocalVue, shallowMount} from '@vue/test-utils';
import TokenTwitterAddress from '../../js/components/token/twitter/TokenTwitterAddress';
import {NotificationMixin} from '../../js/mixins';
import moxios from 'moxios';
import axios from 'axios';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.mixin(NotificationMixin);
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {single: axios};
            Vue.prototype.$routing = {generate: (val, params) => val};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$toasted = {show: () => {}};
            Vue.prototype.$logger = {error: () => {}};
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
        address: 'https://www.twitter.com/testProfile',
        tokenName: 'jasmToken',
        ...props,
    };
}

const initTwitterAuthPopUpEmpty = () => {
    window.twitterProvider = null;
    window.auth = null;
    window.signWithPopup = null;
};

describe('TokenTwitterAddress', () => {
    let wrapper;

    TokenTwitterAddress.methods.initTwitterAuthPopUp = initTwitterAuthPopUpEmpty;
    TokenTwitterAddress.methods.initFireBase = () => {};

    beforeEach(() => {
        wrapper = shallowMount(TokenTwitterAddress, {
            localVue,
            stubs: ['b-tooltip'],
            propsData: createSharedTestProps(),
        });

        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
        wrapper.destroy();
    });

    describe('should compute computedProfile correctly', () => {
        it('When "currentProfile" does not contain a value', async () => {
            await wrapper.setData({
                currentProfile: '',
            });

            expect(wrapper.vm.computedProfile).toBe('token.twitter.empty_address');
        });

        it('When "currentProfile" contains a value', async () => {
            expect(wrapper.vm.computedProfile).toBe('https://www.twitter.com/testProfile');
        });
    });


    it('do $axios request, set currentProfile and submitting correctly and emit "saveTwitterProfile"', async (done) => {
        wrapper.vm.notifySuccess = jest.fn();

        moxios.stubRequest('token_update', {
            status: 200,
        });

        await wrapper.vm.saveTwitterProfile('https://www.twitter.com/testProfile');

        moxios.wait(() => {
            expect(wrapper.vm.notifySuccess).toHaveBeenCalledWith('toasted.success.twitter.added');
            expect(wrapper.vm.currentProfile).toBe('https://www.twitter.com/testProfile');
            expect(wrapper.vm.submitting).toBe(false);
            expect(wrapper.emitted('saveTwitter').length).toBe(1);
            done();
        });
    });

    it('do not $axios request when submitting data is true and the function saveTwitterProfile() is called',
        async () => {
            const twitterUrlTest = 'https://www.twitter.com/testProfile';

            await wrapper.setData({
                submitting: false,
            });

            wrapper.vm.saveTwitterProfile(twitterUrlTest);

            expect(wrapper.vm.currentProfile).toBe(twitterUrlTest);
        }
    );

    it('call saveTwitterProfile(\'\') when deleteProfile() is called', () => {
        wrapper.vm.saveTwitterProfile = function(profile) {
            if ('' === profile) {
                wrapper.vm.$emit('deleteProfileTest');
            }
        };

        wrapper.vm.deleteProfile();

        expect(wrapper.vm.currentProfile).toBe('');
        expect(wrapper.emitted('deleteProfileTest').length).toBe(1);
    });
});
