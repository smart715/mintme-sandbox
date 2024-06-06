const {GENERAL} = require('../../utils/constants');
const moment = require('moment');
const minOptions = 2;
const maxOptions = 32;
const newOption = {title: '', errorMessage: ''};
const newVotingData = {
    title: '',
    description: '',
    endDate: null,
    options: [
        {...newOption},
        {...newOption},
    ],
};

/**
 * @return {string}
 */
function generateEndDate() {
    return moment().add(1, 'day').add(30, 'seconds').format(GENERAL.dateTimeFormatPicker);
}

const storage = {
    namespaced: true,
    state: {
        tokenName: '',
        votings: [],
        currentVoting: {},
        invalidForm: true,
        invalidOptions: true,
        isInitialized: false,
        votingCount: 0,
        ...JSON.parse(JSON.stringify(newVotingData)),
    },
    getters: {
        getTokenName(state) {
            return state.tokenName;
        },
        getVotings(state) {
            return state.votings;
        },
        getCurrentVoting(state) {
            return state.currentVoting;
        },
        getTitle(state) {
            return state.title;
        },
        getDescription(state) {
            return state.description;
        },
        getEndDate(state) {
            return state.endDate;
        },
        getOptions(state) {
            return state.options;
        },
        getVotingData(state) {
            return {
                title: state.title,
                description: state.description,
                endDate: moment(state.endDate ?? generateEndDate(), GENERAL.dateTimeFormatPicker).format(),
                options: state.options.map((option) => {
                    return {title: option.title};
                }),
            };
        },
        canAddOptions(state) {
            return state.options.length < maxOptions;
        },
        canDeleteOptions(state) {
            return state.options.length > minOptions;
        },
        getInvalidForm(state) {
            return state.invalidForm;
        },
        getInvalidOptions(state) {
            return state.invalidOptions;
        },
        getIsInitialized(state) {
            return state.isInitialized;
        },
        getVotingsCount(state) {
            return state.votingCount;
        },
    },
    actions: {
        init({commit, getters}, {tokenName, votings}) {
            if (getters.getTokenName === tokenName) {
                return;
            }

            commit('setTokenName', tokenName);
            commit('setVotings', votings);
            commit('setIsInitialized', true);
        },
        addOption({commit, getters}) {
            if (getters.canAddOptions) {
                commit('addOption');
            }
        },
        deleteOption({commit, getters}, i) {
            if (getters.canDeleteOptions) {
                commit('deleteOption', i);
            }
        },
        updateEndDate({state}) {
            state.endDate = generateEndDate();
        },
        updateVoting({state, commit}, val) {
            if (state.currentVoting.id === val.id) {
                commit('setCurrentVoting', val);
            }
            for (const i in state.votings) {
                if (state.votings[i].id === val.id) {
                    state.votings[i] = val;
                    break;
                }
            }
        },
        updateVotingOption({state}, {key, option}) {
            state.options.some((_, i) => {
                if (i === key) {
                    Object.keys(state.options[i]).forEach((j) => state.options[i][j] = option[j]);
                    return true;
                }
            });
        },
        clearVotingData({state}) {
            const votingData = JSON.parse(JSON.stringify(newVotingData));

            for (const [key, value] of Object.entries(votingData)) {
                state[key] = value;
            }
        },
    },
    mutations: {
        setTokenName(state, payload) {
            state.tokenName = payload;
        },
        setVotings(state, payload) {
            state.votings = payload;
        },
        insertVotings(state, payload) {
            if (!payload.length) {
                state.votings = [];
            } else {
                state.votings.push(...payload);
            };
        },
        setCurrentVoting(state, payload) {
            state.currentVoting = payload;
        },
        setTitle(state, payload) {
            state.title = payload;
        },
        setDescription(state, payload) {
            state.description = payload;
        },
        setEndDate(state, payload) {
            state.endDate = payload;
        },
        addOption(state) {
            state.options.push({...newOption});
        },
        deleteOption(state, payload) {
            state.options.splice(payload, 1);
        },
        setInvalidForm(state, payload) {
            state.invalidForm = payload;
        },
        setInvalidOptions(state, payload) {
            state.invalidOptions = payload;
        },
        setIsInitialized(state, payload) {
            state.isInitialized = payload;
        },
        setActiveVoting(state, payload) {
            state.activeVoting = payload;
        },
        setVotingsCount(state, payload) {
            state.votingCount = payload;
        },
        addVoting(state, payload) {
            if (state.votings) {
                state.votings.unshift(payload);
            } else {
                state.votings = [payload];
            }
        },
        deleteVoting(state, payload) {
            if (!state.votings) {
                return;
            }

            state.votings = state.votings.filter((p) => p.id !== payload.id);
        },
    },
};

export default storage;
