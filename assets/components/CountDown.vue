<template>
    <div v-if="!isDisabled">
        <div class="text-center buy-action" v-if="isEndDate">
            <slot name="content"></slot>
        </div>
        <div class="count-down" v-else>
            <div class="block">
                <p class="digit">{{ days | twoDigits }}</p>
                <p class="text">Days</p>
            </div>
            <div class="block">
                <p class="digit">{{ hours | twoDigits }}</p>
                <p class="text">Hours</p>
            </div>
            <div class="block">
                <p class="digit">{{ minutes | twoDigits }}</p>
                <p class="text">Minutes</p>
            </div>
            <div class="block">
                <p class="digit">{{ seconds | twoDigits }}</p>
                <p class="text">Seconds</p>
            </div>
        </div>
    </div>
</template>


<script>
    export default {
        name: 'CountDown',
        props: {
            endDate: String,
            currentDate: String,
            disabled: String,
        },
        data() {
            return {
                now: Math.trunc((new Date(this.currentDate)).getTime() / 1000),
            };
        },
        mounted() {
            window.setInterval(() => {
                this.now = this.now + 1;
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
                return ((this.days <= 0) &&
                        (this.hours <= 0) &&
                        (this.minutes <=0) &&
                        (this.seconds <= 0));
            },
            isDisabled: function() {
                return this.disabled == 'false' ? false : true;
            },
        },
        filters: {
            twoDigits: function(value) {
                if (value <= 0) {
                    return '00';
                }
                if (value.toString().length <= 1) {
                    return '0' + String(value);
                }
                return value;
            },
        },
    };
</script>
