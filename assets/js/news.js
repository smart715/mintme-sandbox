import AOS from 'aos';
import Vue from 'vue';
import NewsImage from './components/NewsImage';
import store from './storage';

window.Vue = Vue;

AOS.init({
    easing: 'ease-out-back',
    duration: 1600,
    once: true,
});

new Vue({
    el: '#news-selector',
    store,
    components: {NewsImage},
});
