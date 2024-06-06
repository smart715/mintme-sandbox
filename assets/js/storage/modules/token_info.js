import {tokenDeploymentStatus} from '../../utils/constants';

const storage = {
    namespaced: true,
    state: {
        coverImage: null,
        deploys: null,
        indexedDeploys: null,
        tokenAvatar: null,
    },
    getters: {
        getCoverImage: function(state) {
            return state.coverImage;
        },
        getDeploys: function(state) {
            return state.deploys;
        },
        getIndexedDeploys: function(state) {
            return state.indexedDeploys;
        },
        getMainDeploy: function(state) {
            return state.deploys[0];
        },
        getDeploymentStatus: function(state) {
            const mainDeploy = state.deploys[0];

            if (!mainDeploy) {
                return tokenDeploymentStatus.notDeployed;
            }

            if (mainDeploy.pending) {
                return tokenDeploymentStatus.pending;
            }

            return tokenDeploymentStatus.deployed;
        },
        getTokenAvatar: function(state) {
            return state.tokenAvatar;
        },
    },
    mutations: {
        setCoverImage: function(state, value) {
            state.coverImage = value;
        },
        setDeploys: function(state, value) {
            state.deploys = value;

            state.indexedDeploys = value.reduce((object, deploy) => {
                object[deploy.crypto.symbol] = deploy;

                return object;
            }, {});
        },
        setTokenAvatar: function(state, payload) {
            state.tokenAvatar = payload;
        },
    },
};

export default storage;
