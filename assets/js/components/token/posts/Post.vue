<template>
	<div class="post">
		<p>{{ post.content }}</p>
		<span>{{ post.createdAt }}</span>
		<span>{{ post.author }}</span>
	</div>
</template>

<script>
export default {
	name: "Post",
	props: {
		post: Object,
	},
	data() {
		return {};
	},
}
</script>