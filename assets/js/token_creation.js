import Modal from './components/modal/Modal';

import {required, minLength, maxLength, helpers} from 'vuelidate/lib/validators';

const tokenContain = helpers.regex('names', /^[a-zA-Z0-9\s-]*$/u);

new Vue({
    el: '#token',
    components: {
        Modal,
    },
    data: {
        tokenName: '',
        domLoaded: false,
    },
    watch: {
        tokenName: function() {
            if (this.tokenName.replace(/-|\s/g, '').length === 0) {
                this.tokenName = '';
            }
        }
    },
    methods: {
        redirectToProfile: function() {
            location.href = this.$routing.generate('profile-view');
        },
    },
    mounted: function() {
        window.onload = () => this.domLoaded = true;
    },
    validations: {
        tokenName: {
            required,
            tokenContain: tokenContain,
            minLength: minLength(4),
            maxLength: maxLength(255),
        },
    },
});
