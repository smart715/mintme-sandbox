import {createLocalVue, shallowMount} from '@vue/test-utils';
import VotingProposition from '../../js/components/voting/VotingProposition';
import {GENERAL} from '../../js/utils/constants';
import {NotificationMixin} from '../../js/mixins';
import moment from 'moment';
import axios from 'axios';
import moxios from 'moxios';
import Vuex from 'vuex';

const localVue = mockVue();

/**
 * @return {Wrapper<Vue>}
 */
function mockVue() {
    const localVue = createLocalVue();
    localVue.use(Vuex);
    localVue.mixin(NotificationMixin);
    localVue.use({
        install(Vue) {
            Vue.prototype.$axios = {retry: axios, single: axios};
            Vue.prototype.$routing = {generate: (val) => val};
            Vue.prototype.$logger = {error: (val, params) => val, success: (val, params) => val};
            Vue.prototype.$t = (val) => val;
            Vue.prototype.$toasted = {show: () => {}};
        },
    });
    return localVue;
};

/**
 * @param {Object} props
 * @return {Object}
 */
function createSharedTestProps(props = {}) {
    return {
        proposition: {
            title: 'jasm',
            slug: 'title',
            userVotings: Array(10).fill({}),
            creatorProfile: {
                nickname: 'jasmNickname',
                image: {
                    avatar_small: 'avatar_small',
                },
                createdAt: '2000-02-22T24:00:00',
                endDate: '2023-04-21T00:00:00.000Z',
            },
            closed: true,
        },
        isTokenPage: false,
        isOwner: false,
        ...props,
    };
}

/**
 * @param {Object} store
 * @return {Vuex.Store}
 */
function createSharedTestStore(store = {}) {
    return new Vuex.Store({
        modules: {
            voting: {
                namespaced: true,
                mutations: {
                    setCurrentVoting: () => {},
                },
            },
            user: {
                namespaced: true,
                getters: {
                    getId: () => 1,
                },
            },
        },
    });
}

describe('VotingProposition', () => {
    let store;
    let wrapper;
    const url = '/voting/title';

    Object.defineProperty(window, 'location', {
        value: {
            href: url,
        },
        configurable: true,
    });

    beforeEach(() => {
        store = createSharedTestStore();

        wrapper = shallowMount(VotingProposition, {
            localVue: localVue,
            store,
            propsData: createSharedTestProps(),
        });

        moxios.install();
    });

    afterEach(() => {
        moxios.uninstall();
        wrapper.destroy();
    });

    it('Verify that the computed property "info" returns the correct values', () => {
        const info = {
            ...wrapper.vm.info,
            startDate: moment(new Date(wrapper.vm.info.startDate)).format(GENERAL.dateFormat),
            endDate: moment(new Date(wrapper.vm.info.endDate)).format(GENERAL.dateFormat),
        };

        expect(info).toEqual({
            nickname: 'jasmNickname',
            img: 'avatar_small',
            startDate: moment(wrapper.vm.proposition.createdAt).format(GENERAL.dateFormat),
            endDate: moment(wrapper.vm.proposition.endDate).format(GENERAL.dateFormat),
        });
    });

    describe('Verify that the computed property "status" returns the correct values', () => {
        it('When proposition is closed', () => {
            expect(wrapper.vm.status).toBe('voting.proposition.closed');
        });

        it('When the proposition is not closed', async () => {
            await wrapper.setProps({
                proposition: {
                    slug: 'title',
                    userVotings: Array(10).fill({}),
                    creatorProfile: {
                        nickname: 'jasmNickname',
                        image: {
                            avatar_small: 'avatar_small',
                        },
                        createdAt: '2000-02-22T24:00:00',
                        endDate: '2023-04-21T00:00:00.000Z',
                    },
                    closed: false,
                },
            });

            expect(wrapper.vm.status).toBe('voting.proposition.active');
        });
    });

    it('Verify that the computed property "votesCount" returns the correct value', () => {
        expect(wrapper.vm.votesCount).toBe(10);
    });

    describe('Verify that the computed property "votesCount" returns the correct value', () => {
        it('when isOwner = false and isTokenPage = false', () => {
            expect(wrapper.vm.showDeleteIcon).toBe(false);
        });

        it('when isOwner = true and isTokenPage = true', async () => {
            await wrapper.setProps({
                isOwner: true,
                isTokenPage: true,
            });

            expect(wrapper.vm.showDeleteIcon).toBe(true);
        });

        it('when isOwner = true and isTokenPage = false', async () => {
            await wrapper.setProps({
                isOwner: true,
                isTokenPage: false,
            });

            expect(wrapper.vm.showDeleteIcon).toBe(false);
        });

        it('when isOwner = false and isTokenPage = true', async () => {
            await wrapper.setProps({
                isOwner: false,
                isTokenPage: true,
            });

            expect(wrapper.vm.showDeleteIcon).toBe(false);
        });
    });

    it('Verify that the computed property "translationContext" returns the correct value', () => {
        expect(wrapper.vm.translationContext).toStrictEqual({
            title: wrapper.vm.proposition.title,
        });
    });

    it('Verify that "goToShow" is working correctly', () => {
        wrapper.vm.goToShow();

        expect(wrapper.emitted('go-to-show')).toBeTruthy();
    });

    describe('Verify that "deleteProposition" is working correctly', () => {
        it('deleteProposition success', async (done) => {
            wrapper.vm.notifySuccess = jest.fn();

            await wrapper.setData({
                activeProposition: {
                    title: 'jasm',
                    id: 1,
                },
            });

            moxios.stubRequest('delete_voting', {
                status: 200,
                response: {},
            });

            await wrapper.vm.deleteProposition();

            moxios.wait(() => {
                expect(wrapper.vm.showDeletePropositionModal).toBe(false);
                expect(wrapper.vm.activeProposition).toBe(null);
                expect(wrapper.vm.isDeleting).toBe(false);
                expect(wrapper.emitted('proposition-deleted')).toBeTruthy();
                expect(wrapper.emitted('proposition-deleted')[0]).toEqual([{title: 'jasm', id: 1}]);
                expect(wrapper.vm.notifySuccess).toHaveBeenCalledWith('voting.deleted');
                done();
            });
        });

        it('deleteProposition denied ', async (done) => {
            wrapper.vm.notifyError = jest.fn();

            moxios.stubRequest('delete_voting', {
                status: 403,
                response: {
                    message: 'error-message',
                },
            });

            await wrapper.vm.deleteProposition();

            moxios.wait(() => {
                expect(wrapper.vm.notifyError).toHaveBeenCalledWith('voting.error.deleted');
                expect(wrapper.vm.isDeleting).toBe(false);
                done();
            });
        });
    });

    it('Verify that "openDeletePropositionModal" is working correctly', () => {
        wrapper.vm.openDeletePropositionModal(wrapper.vm.proposition);

        expect(wrapper.vm.activeProposition).toBe(wrapper.vm.proposition);
        expect(wrapper.vm.showDeletePropositionModal).toBe(true);
    });
});
