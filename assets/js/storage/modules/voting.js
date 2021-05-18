const {GENERAL} = require('../../utils/constants');
const moment = require('moment');
const minOptions = 2;
const maxOptions = 32;
const newOption = {title: '', errorMessage: ''};
const newVotingData = {
    title: '',
    description: '',
    endDate: generateEndDate(),
    options: [
        {...newOption},
        {...newOption},
    ],
};

/**
 * @return {string}
 */
function generateEndDate() {
    return moment().add(1, 'hour').add(30, 'seconds').format(GENERAL.dateTimeFormatPicker);
}

const storage = {
    namespaced: true,
    state: {
        tokenName: '',
        votings: [],
        currentVoting: {},
        invalidForm: true,
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
                endDate: moment(state.endDate, GENERAL.dateTimeFormatPicker).format(),
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
    },
    actions: {
        init({commit, getters}, {tokenName, votings}) {
            if (getters.getTokenName === tokenName) {
                return;
            }

            commit('setTokenName', tokenName);
            commit('setVotings', votings);
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
        unshiftVoting({state}, voting) {
            state.votings.unshift(voting);
        },
        resetVotingData({state}) {
            Object.keys(newVotingData)
                .forEach((key) => state[key] = JSON.parse(JSON.stringify(newVotingData[key])));
        },
        updateEndDate({state}) {
            state.endDate = generateEndDate();
        },
        updateVoting({state, commit}, val) {
            if (state.currentVoting.id === val.id) {
                commit('setCurrentVoting', val);
            }
            for (let i in state.votings) {
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
    },
    mutations: {
        setTokenName(state, val) {
            state.tokenName = val;
        },
        setVotings(state, val) {
            state.votings = val;
        },
        setCurrentVoting(state, val) {
            state.currentVoting = val;
        },
        setTitle(state, val) {
            state.title = val;
        },
        setDescription(state, val) {
            state.description = val;
        },
        setEndDate(state, val) {
            state.endDate = val;
        },
        addOption(state) {
            state.options.push({...newOption});
        },
        deleteOption(state, i) {
            state.options.splice(i, 1);
        },
        setInvalidForm(state, val) {
            state.invalidForm = val;
        },
    },
};

export default storage;
