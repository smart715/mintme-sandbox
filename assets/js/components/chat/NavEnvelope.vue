<template>
    <counter-wrapper :count="count">
        <font-awesome-icon icon="envelope" size="lg"/>
    </counter-wrapper>
</template>

<script>
import {library} from '@fortawesome/fontawesome-svg-core';
import {faEnvelope} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import CounterWrapper from '../CounterWrapper';

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
        CounterWrapper,
    },
    computed: {
        countTxt: function() {
            return 99 < this.count
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
                .catch((error) => this.$logger.error('get unread messages count response error', error));
        },
    },
    mounted() {
        this.loadCount();
    },
};
</script>
