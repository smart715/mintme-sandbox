<template>
    <div class="countdown" v-if="!isDisabled">
        <div class="text-center" v-if="isEndDate">
            <slot name="content">{{ $t('countdown.time_up') }}</slot>
        </div>
        <div class="countdown-list d-flex justify-content-center" v-else>
            <div class="text-center countdown-item">
                <h2>{{ days | twoDigits }}</h2>
                <p>{{ $t('countdown.days') }}</p>
            </div>
            <div class="text-center countdown-item">
                <h2>{{ hours | twoDigits }}</h2>
                <p>{{ $t('countdown.hours') }}</p>
            </div>
            <div class="text-center countdown-item">
                <h2>{{ minutes | twoDigits }}</h2>
                <p>{{ $t('countdown.minutes') }}</p>
            </div>
            <div class="text-center countdown-item">
                <h2>{{ seconds | twoDigits }}</h2>
                <p>{{ $t('countdown.seconds') }}</p>
            </div>
        </div>
    </div>
</template>


<script>

export default {
    name: 'Countdown',
    props: {
        endDate: String,
        currentDate: String,
        disabled: {type: Boolean, default: false},
    },
    data() {
        return {
            now: Math.trunc((new Date(this.currentDate)).getTime() / 1000),
        };
    },
    mounted() {
        window.setInterval(() => {
            this.now++;
        }, 1000);
    },
    computed: {
        formattedDate: function() {
            return Math.trunc(Date.parse(this.endDate) / 1000);
        },
        seconds: function() {
            return (this.formattedDate - this.now) % 60;
        },
        minutes: function() {
            return Math.trunc((this.formattedDate - this.now) / 60) % 60;
        },
        hours: function() {
            return Math.trunc(
                (this.formattedDate - this.now)
                / 60
                / 60) % 24;
        },
        days: function() {
            return Math.trunc(
                (this.formattedDate - this.now)
                / 60
                / 60
                / 24);
        },
        isEndDate: function() {
            return ((0 >= this.days) &&
                    (0 >= this.hours) &&
                    (0 >=this.minutes) &&
                    (0 >= this.seconds));
        },
        isDisabled: function() {
            return this.disabled;
        },
    },
    filters: {
        twoDigits: function(value) {
            if (0 >= value) {
                return '00';
            }
            if (1 >= value.toString().length) {
                return '0' + String(value);
            }
            return value;
        },
    },
};
</script>
