export default {
	methods: {
		sendNotification: function(message, type) {
			this.$toasted.show(message, {type, icon: `icon-${type}`});
		}
	},
}