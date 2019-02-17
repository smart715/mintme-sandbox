import axios from 'axios';
import axiosRetry from 'axios-retry';

let csrfTokenSelector = document.querySelector('meta[name="csrf-token"]');

if (null !== csrfTokenSelector) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfTokenSelector.getAttribute('content');
}

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

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
