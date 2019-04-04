import {helpers, minLength} from 'vuelidate/lib/validators';
const names = helpers.regex('names', /^[A-Za-zÁ-Źá-ź]+[A-Za-zÁ-Źá-ź\s'‘’`´-]*$/u);

new Vue({
    el: '#profile-creation',
    data: {
        firstName: '',
        lastName: '',
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
    },
});

