import axios from 'axios';
import axiosRetry from 'axios-retry';

let csrfToken = document.querySelector('meta[name="csrf-token"]')
    .getAttribute('content');

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;

const client = axios.create();

axiosRetry(client, {
    retries: Infinity,
    retryDelay: axiosRetry.exponentialDelay,
});

export default {
    install(Vue, options) {
        Vue.prototype.$axios = {
            retry: client,
            single: axios,
        };
    },
};
