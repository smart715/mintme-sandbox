<template>
    <form method="post">
        <div class="row">
            <div class="col-lg-6">
                <email-form></email-form>
            </div>
            <div class="col-lg-6">
                <password-form></password-form>
            </div>
        </div>
    </form>
</template>

<script>
    import EmailForm from './EmailForm';
    import passwordForm from './PasswordForm';
    export default {
        name: 'AccountSettings',
        components: {
            EmailForm,
            passwordForm,
        },
    };
</script>
