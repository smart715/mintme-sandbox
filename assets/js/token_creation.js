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
    },
    validations: {
        tokenName: {
            tokenContain,
            minLength: minLength(4),
            maxLength: maxLength(255),
        },
    },
});
