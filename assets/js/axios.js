import axios from 'axios';
import axiosRetry from 'axios-retry';

const csrfTokenSelector = document.querySelector('meta[name="csrf-token"]');

if (null !== csrfTokenSelector) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfTokenSelector.getAttribute('content');
}

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const client = axios.create();

axiosRetry(client, {
    retries: 2, // the first request + this 2 = total of 3 requests
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
