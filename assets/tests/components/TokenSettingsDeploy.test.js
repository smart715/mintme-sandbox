import {createLocalVue, shallowMount} from '@vue/test-utils';
import TokenSettingsDeploy from '../../js/components/token_settings/TokenSettingsDeploy';
import '../__mocks__/ResizeObserver';
import Vuex from 'vuex';
import {tokenDeploymentStatus} from '../../js/utils/constants';

const $routing = {
    generate: (val, params) => {
        return val
            + (params.name ? '-' + params.name : '')
            + (params.saveSuccess ? '-saveSuccess=' + params.saveSuccess : '')
            + (params.base ? '-' + params.base : '')
            + (params.quote ? '-' + params.quote : '');
    },
};

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use({
        install(Vue) {
            Vue.prototype.$routing = $routing;
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$toasted = {show: (val) => val};
            Vue.prototype.$logger = {error: () => {}};
        },
    });
    return localVue;
}

/**
 * @return {Wrapper<vue>}
 * @param {object} options
 */
function mockDefaultWrapper(options = {}) {
    const propsForTestCorrectlyRenders = {
        currentDescription: 'a'.repeat(200),
        twofaEnabled: false,
        coverImage: '',
        isCreatedOnMintmeSite: true,
    };
    const localVue = mockVue();
    localVue.use(Vuex);
    const store = new Vuex.Store({
        modules: {
            tokenInfo: {
                namespaced: true,
                getters: {
                    getDeploymentStatus: function() {
                        return tokenDeploymentStatus.deployed;
                    },
                    getMainDeploy: function() {
                        return null;
                    },
                },
            },
            tokenSettings: {
                namespaced: true,
                getters: {
                    getTokenName: function() {
                        return 'TEST';
                    },
                    getIsTokenExchanged: function() {
                        return true;
                    },
                },
            },
        },
    });

    return shallowMount(TokenSettingsDeploy, {
        localVue,
        store,
        propsData: propsForTestCorrectlyRenders,
        ...options,
    });
}

describe('TokenSettingsDeploy', () => {
    it('scroll to token release period component on click', () => {
        const wrapper = mockDefaultWrapper();
        const scrollIntoViewMock = jest.fn();
        wrapper.vm.$refs['token-release-period-component'].$el.scrollIntoView = scrollIntoViewMock;

        wrapper.vm.clickReleasePeriod();

        expect(scrollIntoViewMock).toHaveBeenCalledWith({behavior: 'smooth', block: 'center'});
    });
});
