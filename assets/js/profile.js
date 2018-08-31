const showEditFormFirst = document.getElementById('show-edit-form-first').value;

new Vue({
   el: '#profile',
   data: {
        showEditForm: showEditFormFirst,
   },
});
