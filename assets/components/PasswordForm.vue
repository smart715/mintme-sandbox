<template>
<form  action="" >
    <div class="form-group">
        <label for="pwd">New password:</label>
        <input name="pwd" type="password" class="form-control" id="pwd">
    </div>
    <div class="form-group">
        <label for="pwd">Repeat password:</label>
        <input name="rpwd" type="password" class="form-control" id="rpwd">
    </div>
    <button type="submit" class="btn btn-primary float-right px-5">Save</button>
</form>
</template>

<script>
export default {
    name: 'PasswordForm',
};
</script>
