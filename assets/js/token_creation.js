import Modal from './components/modal/Modal';

import {minLength, maxLength, helpers} from 'vuelidate/lib/validators';

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
            tokenContain: tokenContain,
            minLength: minLength(4),
            maxLength: maxLength(255),
        },
    },
});
