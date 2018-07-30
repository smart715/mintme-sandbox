import PersonalData from '../components/PersonalData';
import AccountSettings from '../components/AccountSettings';
new Vue({
    el: '#profile',
    data: {
        tabSelected: 'personalData',
    },
    components: {
        PersonalData,
        AccountSettings,
    },
});
