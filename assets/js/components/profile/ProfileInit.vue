<template>
    <div></div>
</template>

<script>
import {mapMutations} from 'vuex';

export default {
    name: 'ProfileInit',
    props: {
        countries: Object,
        nickname: String,
        firstName: String,
        lastName: String,
        city: String,
        country: String,
        zipCode: String,
        description: String,
    },
    data() {
        return {
            deployTimeout: null,
        };
    },
    created() {
        this.setProfile({
            nickname: this.nickname || '',
            firstName: this.firstName || '',
            lastName: this.lastName || '',
            country: this.country || '',
            city: this.city || '',
            zipCode: this.zipCode || '',
            phoneNumber: this.phoneNumber || '',
            description: this.description || '',
        });
        this.setCountries(this.countries);
    },
    methods: {
        ...mapMutations('profile', [
            'setProfile',
            'setCountries',
        ]),
    },
};
</script>
