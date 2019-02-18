<template>
    <div class="card">
        <div class="card-header">
            Shoutbox
            <span class="card-header-icon">
                <font-awesome-icon
                    icon="circle"
                    class="text-success text-xs"/>
            </span>
        </div>
        <div class="card-body p-0">
            <div class="fix-height">
                <div
                    class="chat px-3"
                    v-for="msg in messages"
                    v-bind:key="msg">
                    <span class="align-middle">
                        <img
                            src="../../../img/avatar.png"
                            alt="avatar">
                    </span>
                    <span class="pl-2 align-middle">
                        {{ msg }}
                    </span>
                </div>
            </div>
            <div class="py-2  px-3">
                <textarea class="form-control" v-model="message" @keyup="send" v-if="loggedIn"></textarea>
                <template v-else>
                    <a :href="loginUrl" class="btn btn-primary">Log In</a>
                    <span class="px-2">or</span>
                    <a :href="signupUrl">Sign Up</a>
                </template>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'TokenShoutBox',
    props: {
        loginUrl: String,
        signupUrl: String,
        loggedIn: Boolean,
        messages: Array,
        user: String,
        currentDate: String,
    },
    data() {
        return {
            message: '',
        };
    },
    methods: {
        send: function(e) {
            if (e.keyCode === 13 && this.message.trim().length > 0) {
                this.messages.push('(' + this.currentDate + ') ' + this.user + ': ' + this.message);
                this.message = '';
            }
        },
    },
};
</script>

