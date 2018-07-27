import '../scss/main.sass';
import BootstrapVue from 'bootstrap-vue';

window.Vue = require('vue');

Vue.use(BootstrapVue);

Vue.options.delimiters = ['{[', ']}'];
