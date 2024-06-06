import {createLocalVue, shallowMount} from '@vue/test-utils';
import TokenSettingsAdvanced from '../../js/components/token_settings/TokenSettingsAdvanced';
import Vuex from 'vuex';
import tokenSettings from '../../js/storage/modules/token_settings';

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
    localVue.use(Vuex);

    return localVue;
}

const localVue = mockVue();
const store = new Vuex.Store({
    modules: {
        tokenInfo: {
            namespaced: true,
            getters: {
                getDeploymentStatus: function() {
                    return '';
                },
                getMainDeploy: function() {
                    return '';
                },
            },
        },
        tokenSettings,
    },
});

/**
 * @param {object} propsData
 * @param {bool} isTokenDeployed
 * @return {Wrapper<Vue>}
 */
function mock(propsData = {}, isTokenDeployed = {}) {
    return shallowMount(TokenSettingsAdvanced, {
        store,
        localVue,
        propsData: {
            websocketUrl: '',
            ...propsData,
        },
        computed: {
            isTokenDeployed: function() {
                return isTokenDeployed;
            },
        },
    });
}

describe('TokenSetthingsAdvanced', () => {
    describe('showTokenReleaseAddress', () => {
        it('should be true if token created on mintme and deployed', () => {
            const wrapper = mock(
                {
                    isCreatedOnMintmeSite: true,
                },
                true
            );

            expect(wrapper.vm.showTokenReleaseAddress).toBe(true);
        });

        it('should be false if token created on mintme and not deployed', () => {
            const wrapper = mock(
                {
                    isCreatedOnMintmeSite: true,
                    websocketUrl: 'websocket',
                },
                false
            );

            expect(wrapper.vm.showTokenReleaseAddress).toBe(false);
        });

        it('should be false if token is not created on mintme and deployed', () => {
            const wrapper = mock(
                {
                    isCreatedOnMintmeSite: false,
                },
                true
            );

            expect(wrapper.vm.showTokenReleaseAddress).toBe(false);
        });

        it('should be false if token is not created on mintme and not deployed', () => {
            const wrapper = mock(
                {
                    isCreatedOnMintmeSite: true,
                },
                false
            );

            expect(wrapper.vm.showTokenReleaseAddress).toBe(false);
        });
    });
});
