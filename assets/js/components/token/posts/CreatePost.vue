<template>
	<div class="card h-100">
		<div class="card-header">
            <slot name="title">Create post</slot>
        </div>
        <div class="card-body">
            <div class="form-group">  
                <label for="amount">
                    Required amount of tokens to see this post:
                </label>
                <input class="form-control form-control-lg h-100"
                    name="amount"
                    type="text"
                    step="0.0001"
                    v-model="amount"
                    @keypress="checkInput"
                    @paste="checkInput"
                >
            </div>
            <div class="form-group">
                <bbcode-help class="float-right mt-2" placement="right" />
                <bbcode-editor class="form-control w-100"
                    @change="onContentChange"
                    @input="onContentChange"
                />
                <div class="text-sm text-danger"
                    v-show="content.length > 0 && !$v.content.required"
                >
                    Content cannot be empty.
                </div>
                <div class="text-sm text-danger"
                    v-show="!$v.content.maxLength"
                >
                    Content cannot be more than {{ maxContentLength }} characters.
                </div>
            </div>
            <button class="btn btn-primary"
                :disabled="$v.$invalid"
                @click="savePost"
            >
                Save
            </button>
        </div>
	</div>
</template>

<script>
import BbcodeEditor from '../../bbcode/BbcodeEditor';
import BbcodeHelp from '../../bbcode/BbcodeHelp';
import {required, minLength, maxLength, numeric} from 'vuelidate/lib/validators';

export default {
	name: 'CreatePost',
	components: {BbcodeEditor, BbcodeHelp},
	data() {
		return {
			content: '',
            amount: 0,
            minContentLength: 2,
            maxContentLength: 500,
		};
	},
    methods: {
        onContentChange(content) {
            this.content = content;
        },
        savePost() {
            this.$v.$touch();

            if (this.$v.$invalid) {
                return;
            }

            this.$axios.single.post(this.$routing.generate('create_post'), {
                content: this.content,
                amount: this.amount,
            }).then(console.log, console.log);
        },
        checkInput: function() {
            let selectionStart = event.target.selectionStart;
            let selectionEnd = event.target.selectionEnd;
            let amount = event.srcElement.value;
            let regex = new RegExp(`^[0-9]{0,8}(\\.[0-9]{0,4})?$`);
            let input = event instanceof ClipboardEvent
                ? event.clipboardData.getData('text')
                : String.fromCharCode(!event.charCode ? event.which : event.charCode);

            if (!regex.test(amount.slice(0, selectionStart) + input + amount.slice(selectionEnd))) {
                event.preventDefault();
                return false;
            }

            return true;
        },
    },
    validations() {
        return {
            content: {
                required: (val) => {
                    return required(val.replace(/\[\/?(?:b|i|u|s|ul|ol|li|p|s|url|img|h1|h2|h3|h4|h5|h6)*?.*?\]/g, '').trim());
                },
                minLength: minLength(this.minContentLength),
                maxLength: maxLength(this.maxContentLength),
            },
            amount: {
                numeric,
            },
        }
    }
}
</script>