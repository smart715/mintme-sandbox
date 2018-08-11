<template>
<form method="POST" action="" >
    <div class="form-group">
        <label for="email">Change email:</label>
        <input name="tempEmail" type="text"  id="email" class="form-control"/>
    </div>
    <button type="submit" class="btn btn-primary float-right px-5">Save</button>
</form>
</template>

<script>
export default {
    name: 'EmailForm',
};
</script>
