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
        onEmailSubmit: function() {
            this.$v.$touch();
            if (this.email !== this.initialEmail && !this.$v.$invalid) {
                this.$refs.emailForm.submit();
            }
        },
        onEmailKeyUp: function(event) {
            this.$v.$touch();
            this.email = this.$refs.email.value;
            if (this.email !== this.initialEmail && !this.$v.$invalid) {
                this.$refs.emailButton.disabled = false;
            } else {
                this.$refs.emailButton.disabled = true;
            }
        },
    },
});


