import {isValidEmail} from './utils.js';
new Vue({
    el: '#settings',
    data: {
        initialEmail: false,
    },
    mounted: function() {
        this.initialEmail = this.$refs.email.value;
        console.log(this.initialEmail);
    },
    methods: {
        onEmailSubmit: function() {
            if(this.$refs.email.value !== this.initialEmail && isValidEmail(this.$refs.email.value)) {
                this.$refs.emailForm.submit();
            }
        },
        onEmailKeyUp: function(event) {
            if(this.$refs.email.value !== this.initialEmail  && isValidEmail(this.$refs.email.value)) {
                this.$refs.emailButton.disabled = false;
            }
            else {
                this.$refs.emailButton.disabled = true;
            }
        },
    },
});


