<template>
    <div
        id="nav-profile"
        class="pr-0 dropdown float-right nav-dropdown"
        :class="{ 'show': visibleProfileMenu }"
        @mouseover="showProfileMenu"
        @mouseleave="hideProfileMenu"
    >
        <a
            class="nav-link pl-3 pr-0 dropdown-toggle c-pointer d-flex align-items-center"
            aria-haspopup="true"
            :aria-expanded="visibleProfileMenu"
            v-on-clickaway="hideProfileMenu"
            tabindex="0"
        >
            <avatar
                class="mr-2"
                size="middle"
                type="profile"
                :image="avatarUrl"
            ></avatar>
            <span class="dropdown-label">
                {{ $t('navbar.my_account') }}
            </span>
        </a>
        <div
            class="dropdown-menu dropdown-menu-right align-self-end align-self-lg-center profile-menu"
            :class="{ 'show': visibleProfileMenu }"
        >
            <slot></slot>
        </div>
    </div>
</template>

<script>
import Avatar from '../Avatar';
import {directive as onClickaway} from 'vue-clickaway';

export default {
    name: 'ProfileDropdown',
    components: {
        Avatar,
    },
    directives: {
        onClickaway,
    },
    props: {
        avatarUrl: String,
    },
    data() {
        return {
            visibleProfileMenu: false,
        };
    },
    methods: {
        showProfileMenu: function() {
            this.visibleProfileMenu = true;
        },
        hideProfileMenu: function() {
            this.visibleProfileMenu = false;
        },
    },
    mounted() {
        this.$emit('mounted');
    },
};
</script>
