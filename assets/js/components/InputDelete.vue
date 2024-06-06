<template>
    <div v-if="hasInputError" class="input-group-prepend ">
        <span
            class="input-group-text bg-transparent position-relative ml-n4"
            @click="$emit('clear-input')"
        >
            <font-awesome-icon icon="times" class="text-danger c-pointer" />
        </span>
    </div>
</template>

<script>
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';

export default {
    name: 'InputDelete',
    components: {
        FontAwesomeIcon,
    },
    props: {
        hasInputError: Boolean,
    },
};
</script>
