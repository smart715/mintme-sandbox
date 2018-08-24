<template>
    <div :class="containerClass">
        <div class="card h-100">
            <div class="card-header">
                Description
                <span class="card-header-icon text-white">
                    <font-awesome-icon
                        icon="question"
                        class="m-0 p-1 h4 bg-orange rounded-circle square"
                    />
                </span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col">
                        Lorem Ipsum is simply dummy text of the printing and
                        typesetting industry. Lorem Ipsum has been the
                        industry's standard dummy text ever since the 1500s,
                        when an unknown printer took a galley of type and
                        scrambled it to make a type specimen book.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'TokenIntroductionDescription',
    props: {
        containerClass: String,
    },
    data() {
        return {};
    },
};
</script>
