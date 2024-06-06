export default {
    namespaced: true,
    state: {
        countries: [],
        countriesMap: [],
        profile: {
            lastName: '',
            firstName: '',
            phoneNumber: '',
            country: '',
            city: '',
            zipCode: '',
            description: '',
        },
    },
    getters: {
        getCountries(state) {
            return state.countries;
        },
        getCountriesMap(state) {
            return state.countriesMap;
        },
        getProfile(state) {
            return state.profile;
        },
    },
    mutations: {
        setCountries(state, payload) {
            state.countriesMap = payload;
            state.countries = Object.keys(payload);
        },
        setProfile(state, payload) {
            state.profile = payload;
        },
    },
};
