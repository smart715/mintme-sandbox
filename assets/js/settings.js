import Passwordmeter from './components/PasswordMeter';

new Vue({
    el: '#passwordmeter',
    components: {Passwordmeter},
    data: {
        password: '',
    },
});
import {required, email} from 'vuelidate/lib/validators';

new Vue({
    el: '#settings',
    data: {
        initialEmail: '',
        email: '',
    },
    mounted: function() {
        this.initialEmail = this.$refs.email.value;
        this.email = this.initialEmail;
    },
    validations: {
        email: {
            required,
            email,
        },
    },
    methods: {
        onEmailKeyUp: function(event) {
            this.$v.$touch();
            this.email = this.$refs.email.value;
            if (this.email !== this.initialEmail && !this.$v.$invalid) {
                this.$refs.emailButton.disabled = false;
            } else {
                this.$refs.emailButton.disabled = true;
            }
        },
        onEmailSubmit: function() {
            if (this.email !== this.initialEmail && !this.$v.$invalid) {
                this.$refs.emailForm.submit();
            }
        },
    },
});


