<template>
	<div class="card h-100">
		<div class="card-header">
			<slot name="title">Posts</slot>
		</div>
		<div class="card-body">
			Lorem ipsum dolor sit amet, consectetur adipisicing elit. Dolorem reiciendis, aspernatur quos iusto hic exercitationem corporis necessitatibus nulla aliquam deleniti laudantium maiores, labore pariatur porro sequi excepturi, ipsa distinctio quidem?
		</div>
	</div>
</template>

<script>
export default {
	name: 'Posts',
	data() {
		return {
			hola: 'hola',
		}
	},
}
</script>