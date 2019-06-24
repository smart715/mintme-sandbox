import LimitedTextarea from './components/LimitedTextarea.vue';
import {minLength, helpers} from 'vuelidate/lib/validators';
const names = helpers.regex('names', new RegExp(/^[\p{L}]+[\p{L}\s'‘’`´-]*$/, 'u'));
const city = helpers.regex('city', new RegExp(/^[\p{L}\s-]+$/, 'u'));

new Vue({
    el: '#profile',
    data: {
        showEditForm: false,
        firstName: '',
        lastName: '',
        city: '',
    },
    mounted: function() {
        this.firstName = this.$refs.firstName.getAttribute('value');
        this.lastName = this.$refs.lastName.getAttribute('value');
        this.city = this.$refs.city.getAttribute('value');
        this.showEditForm = this.$refs.editFormShowFirst.value;
    },
    components: {
        LimitedTextarea,
    },
    validations: {
        firstName: {
            helpers: names,
            minLength: minLength(2),
        },
        lastName: {
            helpers: names,
            minLength: minLength(2),
        },
        city: {
            helpers: city,
            minLength: minLength(2),
        },
    },
});

