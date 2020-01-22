import Modal from './components/modal/Modal';
import {required, minLength, maxLength, helpers} from 'vuelidate/lib/validators';
import {
    tokenNameValidChars,
    tokenValidFirstChars,
    tokenValidLastChars,
    tokenNoSpaceBetweenDashes,
} from './utils/constants';

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
        },
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
            validFirstChars: (value) => !tokenValidFirstChars(value),
            validLastChars: (value) => !tokenValidLastChars(value),
            noSpaceBetweenDashes: (value) => !tokenNoSpaceBetweenDashes(value),
            validChars: tokenNameValidChars,
            minLength: minLength(4),
            maxLength: maxLength(255),
        },
    },
});
