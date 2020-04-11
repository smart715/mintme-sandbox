<template>
	<div class="card h-100">
		<div class="card-header">
            <slot name="title">Create post</slot>
        </div>
        <div class="card-body">
            <form>
                <div class="form-group">  
                <label for="amount">
                    Required amount of tokens to see this post:
                </label>
                <input class="form-control form-control-lg h-100" name="amount" type="number" min="0.0001">
            </div>
            <div class="form-group">
                <bbcode-help class="float-right mt-2" placement="right" />
                <bbcode-editor class="form-control w-100" :value="value"/>
            </div>
            <button class="btn btn-primary" type="submit">Save</button>
            </form>
        </div>
	</div>
</template>

<script>
import BbcodeEditor from '../../bbcode/BbcodeEditor';
import BbcodeHelp from '../../bbcode/BbcodeHelp';

export default {
	name: 'CreatePost',
	components: {BbcodeEditor, BbcodeHelp},
	data() {
		return {
			value: '',
		};
	}
}
</script>