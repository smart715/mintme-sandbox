import Passwordmeter from './components/PasswordMeter';

new Vue({
    el: '#reset',
    components: {Passwordmeter},
    data: {
        password: '',
        disabled: false,
    },
    methods: {
        toggleError: function(val) {
            this.disabled = val;
        },
    },
});
