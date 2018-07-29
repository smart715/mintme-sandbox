<template>
<form action="">
    <div class="row">
        <div class="col-lg-6">
            <div class="form-group">
                <label for="website">Website:</label>
                <input
                    name="website"
                    type="url"
                    class="form-control"
                    id="website"/>
            </div>
            <div class="form-group">
                <label for="lname">Facebook:</label>
                <input
                    name="facebook"
                    type="url"
                    class="form-control"
                    id="facebook"/>
            </div>
            <div class="form-group">
                <label for="youtube">Youtube:</label>
                <input
                    name="youtube"
                    type="url"
                    class="form-control"
                    id="youtube"/>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="form-group">
                <label for="desc">Token Description:</label>
                <textarea
                    name="desc"
                    class="form-control"
                    rows="5" id="tokendesc">
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
    name: 'TokenData',
};
</script>

