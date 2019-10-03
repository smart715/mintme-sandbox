import Passwordmeter from './components/PasswordMeter';
import ApiKeys from './components/ApiKeys';

new Vue({
    el: '#settings',
    components: {Passwordmeter, ApiKeys},
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
