import Modal from './components/modal/Modal';
import {minLength, maxLength, alphaNum} from 'vuelidate/lib/validators';

new Vue({
    el: '#token',
    components: {
        Modal,
    },
    data: {
        tokenName: '',
        domLoaded: false,
    },
    mounted: function() {
        window.onload = () => this.domLoaded = true;
    },
    validations: {
        tokenName: {
            alphaNum,
            minLength: minLength(4),
            maxLength: maxLength(255),
        },
    },
});
