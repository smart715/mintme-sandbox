<template>
    <div class="d-flex aling-items-center justify-content-center verify-code-wrp">
        <input
            v-for="n in codeLength"
            :key="n"
            type="text"
            maxlength="1"
            class="two-fa-border text-center form-control mx-1 mx-sm-2"
            :class="{'filled': inputCode[n-1]}"
            :ref="`input_${n}`"
            :disabled="disabled"
            @keydown="onKeyDown($event, n)"
            @input="onInput($event, n)"
            @paste.prevent="onPaste($event, n)"
            inputmode="numeric"
        />
    </div>
</template>

<script>
export default {
    name: 'VerifyCode',
    props: {
        codeLength: {
            type: Number,
            default: 6,
        },
        disabled: Boolean,
        focused: {
            type: Boolean,
            default: true,
        },
    },
    data() {
        return {
            inputCode: new Array(this.codeLength),
            codeEntered: false,
        };
    },
    mounted() {
        if (this.focused) {
            this.focus();
        }
    },
    watch: {
        inputCode() {
            this.checkInputCode();
        },
    },
    methods: {
        onPaste(event, inputNum) {
            const clipboardData = event.clipboardData.getData('text/plain');

            if (!this.isDigits(clipboardData)) {
                return;
            }

            for (let i = 0; i < clipboardData; i++) {
                if (inputNum <= this.codeLength) {
                    this.$refs[`input_${inputNum}`][0].value = clipboardData[i];
                    this.$refs[`input_${inputNum}`][0].focus();
                    this.$set(this.inputCode, inputNum - 1, clipboardData[i]);
                    inputNum++;
                }
            }
        },
        onKeyDown(event, i) {
            if (8 !== event.keyCode) {
                return;
            }

            if (!event.target.value && 1 < i) {
                this.$refs[`input_${i-1}`][0].focus();
            }

            this.$set(this.inputCode, i - 1, '');
        },
        onInput(event, i) {
            if (!isFinite(event.target.value)) {
                event.target.value = '';
                return;
            }

            if (event.target.value && i < this.codeLength) {
                this.$refs[`input_${i+1}`][0].focus();
            }

            this.$set(this.inputCode, i - 1, event.target.value[0]);
        },
        focus(inputNum) {
            const refName = `input_${inputNum ? inputNum : 1}`;

            if (this.$refs[refName]) {
                setTimeout(() => this.$refs[refName][0].focus()); // focus input on next frame
            }
        },
        checkInputCode() {
            if (this.codeEntered) {
                this.codeEntered = false;
                this.$emit('code-entered', null);
            }

            if (-1 === this.inputCode.findIndex((val) => !val)) {
                this.codeEntered = true;
                this.$emit('code-entered', this.inputCode.join(''));
            }
        },
        isDigits(str) {
            return /^\d+$/.test(str);
        },
        clearInput() {
            for (let i = 0; i < this.inputCode.length; i++) {
                this.$refs[`input_${i+1}`][0].value = '';
                this.$set(this.inputCode, i, null);
            }
        },
    },
};
</script>
