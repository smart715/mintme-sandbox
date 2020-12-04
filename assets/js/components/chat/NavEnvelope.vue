<template>
    <a class="position-relative">
        <font-awesome-icon icon="envelope" size="lg"/>
        <div
            v-if="count > 0"
            class="nav-envelope-badge d-flex justify-content-center align-items-center position-absolute">
            <span class="text-bold">
                {{ countTxt }}
            </span>
        </div>
    </a>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faEnvelope} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {LoggerMixin} from '../../mixins';

library.add(faEnvelope);

export default {
    name: 'NavEnvelope',
    props: {
        url: String,
    },
    data() {
        return {
            count: 0,
        };
    },
    components: {
        FontAwesomeIcon,
    },
    mixins: [
        LoggerMixin,
    ],
    computed: {
        countTxt: function() {
            return this.count > 99
                ? '99+'
                : this.count;
        },
    },
    methods: {
        loadCount: function() {
            this.$axios.retry.get(this.$routing.generate('get_unread_messages_count'))
                .then((res) => {
                    this.count = res.data;
                })
                .catch((error) => this.sendLogs('error', 'get unread messages count response error', error));
        },
    },
    mounted() {
        this.loadCount();
    },
};
</script>
