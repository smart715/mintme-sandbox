<template>
<form action="">
    <div class="row">
        <div class="col-lg-6">
            <div class="form-group">
                <label for="fname">First name:</label>
                <input
                    name="fname"
                    type="text"
                    class="form-control"
                    id="fname"/>
            </div>
            <div class="form-group">
                <label for="lname">Last name:</label>
                <input
                    name="lname"
                    type="text"
                    class="form-control"
                    id="lname"/>
            </div>
            <div class="form-group">
                <label for="profile-fburl">City:</label>
                <input
                    name="city"
                    type="text"
                    class="form-control"
                    id="city"/>
            </div>
            <div class="form-group">
                <label for="country">Country:</label>
                <input
                    name="country"
                    type="text"
                    class="form-control"
                    id="country"/>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="form-group">
                <label for="desc">Description:</label>
                <textarea
                    name="desc"
                    class="form-control"
                    rows="5" id="desc">
                </textarea>
            </div>
            <button
                type="submit"
                class="btn btn-primary float-right">
                Submit
            </button>
        </div>
    </div>
</form>
</template>

<script>
export default {
    name: 'PersonalData',
};
</script>

