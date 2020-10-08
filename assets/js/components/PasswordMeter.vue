<template>
    <div>
        <slot></slot>
        <div class="pwdmeter">
            <meter max="5" :value="pwdscore"></meter>
        </div>
        <div class="py-2 mb-2 bg-danger text-white text-center" v-if="strengthtext">
            <ul class="pl-2 pr-2 m-0 list-unstyled">
              <li v-if="strengthtext === 1">{{ $t('passwordmeter.strength_1') }}</li>
              <li v-if="strengthtext === 2">{{ $t('passwordmeter.strength_2') }}</li>
              <li v-if="strengthtext === 3">{{ $t('passwordmeter.strength_3') }}</li>
              <li v-if="strengthtext === 4">{{ $t('passwordmeter.strength_4') }}</li>
            </ul>
        </div>
    </div>
</template>

<script>
import zxcvbn from 'zxcvbn';

export default {
    name: 'passwordmeter',
    props: {
        password: String,
    },
    data: function() {
        return {
            pwdscore: 0,
            strengthtext: 0,
        };
    },
    watch: {
        password: function(val) {
            let result = zxcvbn(val);

            if (val !== '') {
                result.score = result.score + 1;
            }

            if (val.length <= 7 && result.score >= 4) {
                result.score = 3;
            }

            this.pwdscore = result.score;

            if (val.length <= 7 && result.score >= 1) {
                this.strengthtext = 1;
            } else if (val.length >= 8 && result.score <= 5) {
                let number = 0;
                let uppercase = 0;
                let lowercase = 0;

                if (/\d/.test(val)) {
                    number = 1;
                }

                if (/[a-z]/.test(val)) {
                    lowercase = 1;
                }

                if (/[A-Z]/.test(val)) {
                    uppercase = 1;
                }

                if (number + uppercase + lowercase !== 3) {
                    this.strengthtext = 2;
                } else if (val.length > 72) {
                    this.strengthtext = 3;
                } else if (/\s/.test(val)) {
                    this.strengthtext = 4;
                } else {
                    this.strengthtext = 0;
                }
            } else {
                if (/\s/.test(val)) {
                    this.strengthtext = 4;
                } else {
                    this.strengthtext = 0;
                }
            }
        },
        strengthtext: function(val) {
            this.$emit('toggle-error', !!val);
        },
    },
};
</script>
