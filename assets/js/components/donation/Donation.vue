<template>
    <div class="container-fluid px-0">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-6 pr-lg-2 mt-3">
                <div class="h-100">
                    <div class="h-100 donation">
                        <div class="donation-header text-left">
                            <span v-if="loggedIn">Donations</span>
                            <span v-else>To make a donation you have to be logged in</span>
                        </div>
                        <div class="donation-body">
                            <div v-if="loggedIn">
                            </div>
                            <div v-else>
                                <div id="tab-login-form-container" class="p-md-4"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {NotificationMixin, LoggerMixin} from '../../mixins';

export default {
    name: 'Donation',
    mixins: [NotificationMixin, LoggerMixin],
    components: {

    },
    props: {
        market: Object,
        loggedIn: Boolean,
        isOwner: Boolean,
        userId: Number,
    },
    data() {
        return {

        };
    },
    computed: {

    },
    mounted() {
        if (!this.loggedIn) {
            this.loadLoginForm();
        }
    },
    methods: {
        loadLoginForm: function() {
            this.$axios.retry.get(this.$routing.generate('login', {
                formContentOnly: true,
            }))
                .then((res) => {
                    let el = document.getElementById('tab-login-form-container');
                    el.innerHTML = res.data;
                })
                .catch((err) => {
                    this.notifyError('Something went wrong. Try to reload the page.');
                    this.sendLogs('error', 'Can not delete API Client', err);
                });
        },
    },
};
</script>
