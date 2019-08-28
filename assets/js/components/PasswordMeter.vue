<template>
    <div>
        <slot></slot>
        <div class="pwdmeter">
            <meter max="5" :value="pwdscore"></meter>
        </div>
        <div class="py-2 mb-2 bg-danger text-white text-center" v-if="strengthtext">
            <ul class="pl-2 pr-2 m-0 list-unstyled">
                <li v-if="strengthtext === 1">This value is too short. It should have 8 characters or more.</li>
                <li v-if="strengthtext === 2">
                    The password must contain at least one uppercase letter, a lowercase letter, and a number.
                </li>
                <li v-if="strengthtext === 3">This value is too long. It should have 72 characters or less.</li>
            </ul>
        </div>
    </div>
</template>

<script>
import * as zxcvbn from 'zxcvbn';
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
            } else if (val.length >= 8 && result.score <= 4) {
                let number = 0;
                let uppercase = 0;
                let lowercase = 0;

                for (let i in val) {
                    if (val.hasOwnProperty(i)) {
                        const character = val[i];

                        if (!isNaN(character * 1)) {
                            number = 1;
                            continue;
                        }

                        switch (character) {
                            case character.toUpperCase(): uppercase = 1; break;
                            case character.toLowerCase(): lowercase = 1; break;
                        }
                    }
                }

                if (number + uppercase + lowercase !== 3) {
                    this.strengthtext = 2;
                } else if (val.length > 72) {
                    this.strengthtext = 3;
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
