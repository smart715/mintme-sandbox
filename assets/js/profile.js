import PersonalDataForm from '../components/PersonalDataForm';
import AccountSettings from '../components/AccountSettings';
new Vue({
    el: '#profile',
    data: {
        tabSelected: 'personalData',
    },
    components: {
        PersonalDataForm,
        AccountSettings,
    },
});
