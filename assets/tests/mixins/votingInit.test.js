import Vue from 'vue';
import {createLocalVue, shallowMount} from '@vue/test-utils';
import VotingInitMixin from '../../js/mixins/voting_init';
import Vuex from 'vuex';
import voting from '../../js/storage/modules/voting';
const Component = Vue.component('foo', {
    template: '<div></div>',
    mixins: [VotingInitMixin],

});

/**
 * @return {VueConstructor}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);

    return localVue;
}

/**
 * @param {Object} props
 * @param {Object} overrideVoting
 * @return {Wrapper<Vue>}
 */
function createWrapper(props = {}, overrideVoting = {}) {
    const localVue = mockVue();
    const store = new Vuex.Store({
        modules: {
            voting: {
                ...voting,
                ...overrideVoting,
            },
        },
    });

    const wrapper = shallowMount(Component, {
        localVue,
        store,
        propsData: {
            tokenNameProp: 'foo',
            votingsProp: [],
            ...props,
        },
    });

    return wrapper;
}

describe('VotingInitMixin', () => {
    it('should should call init', () => {
        let initCalled = false;
        createWrapper(
            {},
            {
                actions: {
                    ...voting.actions,
                    init: () => initCalled = true,
                },
            }
        );

        expect(initCalled).toBe(true);
    });
});
