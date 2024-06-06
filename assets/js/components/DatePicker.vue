<template>
    <vue-date-picker
        :value="value"
        @input="updateValue"
        :config="realConfig"
    >
    </vue-date-picker>
</template>
<script>
// This a wrapper for vue-bootstrap-datetimepicker
// You can use v-model as you would with the normal component
import VueDatePicker from 'vue-bootstrap-datetimepicker';
import {library} from '@fortawesome/fontawesome-svg-core';
import {
    faArrowUp,
    faArrowDown,
    faChevronLeft,
    faChevronRight,
    faCalendarCheck,
} from '@fortawesome/free-solid-svg-icons';
import {
    faClock,
    faCalendar,
    faTrashAlt,
    faTimesCircle,
} from '@fortawesome/free-regular-svg-icons';

library.add(
    faArrowUp,
    faArrowDown,
    faChevronLeft,
    faChevronRight,
    faCalendarCheck,
    faClock,
    faCalendar,
    faTrashAlt,
    faTimesCircle,
);

export default {
    name: 'DatePicker',
    components: {
        VueDatePicker,
    },
    props: {
        value: {
            type: [String, Date],
            Default: null,
        },
        config: {
            type: Object,
            Default: {},
        },
    },
    computed: {
        realConfig: function() {
            const icons = {
                time: 'far fa-clock',
                date: 'far fa-calendar',
                up: 'fas fa-arrow-up',
                down: 'fas fa-arrow-down',
                previous: 'fas fa-chevron-left',
                next: 'fas fa-chevron-right',
                today: 'fas fa-calendar-check',
                clear: 'far fa-trash-alt',
                close: 'far fa-times-circle',
            };

            return {
                icons,
                ...this.config,
            };
        },
    },
    methods: {
        updateValue: function(value) {
            this.$emit('input', value);
        },
    },
};
</script>
