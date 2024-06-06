import {createLocalVue, shallowMount} from '@vue/test-utils';
import Vuex from 'vuex';
import axios from 'axios';
import moxios from 'moxios';
import TokenCoverImage from '../../js/components/token/TokenCoverImage';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
        },
    });

    return localVue;
}

/**
 * @param {Object} props
 * @return {Object}
 */
function createCoverImgProps(props = {}) {
    return {
        initImage: '/uploads/images/',
        entryPoint: true,
        editable: false,
        tokenName: 'MySuperToken',
        ...props,
    };
};

/**
 * @param {Object} mutations
 * @return {Vuex.Store}
 */
function createCoverImgStore(mutations) {
    return new Vuex.Store({
        modules: {
            tokenInfo: {
                mutations,
                namespaced: true,
                getters: {
                    getCoverImage: () => {},
                },
            },
        },
    });
};

describe('TokenCoverImage', () => {
    let wrapper;
    let mutations;
    let store;

    beforeEach(() => {
        mutations = {
            setCoverImage: jest.fn(),
        };

        store = createCoverImgStore(mutations);

        wrapper = shallowMount(TokenCoverImage, {
            localVue: localVue,
            sync: false,
            store: store,
            propsData: createCoverImgProps(),
        });

        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
    });

    it('should setCoverImage when entryPoint is true', () => {
        expect(mutations.setCoverImage.mock.calls).toHaveLength(1);
    });

    it('should save img to storage on updateCoverImage', () => {
        wrapper.vm.updateCoverImage('superImage.png');

        expect(mutations.setCoverImage.mock.calls).toHaveLength(2);
    });
});
