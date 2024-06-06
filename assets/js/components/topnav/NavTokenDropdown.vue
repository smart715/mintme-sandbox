<template>
    <li v-if="tokens" class="nav-item dropdown nav-dropdown-hovered">
        <a
            class="nav-link dropdown-toggle d-flex align-items-center justify-content-xl-center"
            role="button"
            data-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="true"
            @click.prevent="toggleDropdown"
        >
            <avatar
                size="small"
                type="token"
                img-class="mb-1"
                class="mr-2 tokens-dropdown-avatar"
                :image="defaultTokenAvatar"
            />
            <span class="flex-1">{{ $t('navbar.my_token') }}</span>
        </a>
        <div
            class="dropdown-menu xl-hide"
            aria-labelledby="navbarDropdown"
            :class="{'show': show}"
        >
            <a
                v-for="token in tokens"
                :key="token.name"
                class="dropdown-item d-flex align-items-center"
                :href="$routing.generate('token_show_intro', {'name': token.name})"
                v-b-tooltip="getTooltip(token.name)"
            >
                <avatar
                    size="small"
                    type="token"
                    img-class="mb-1"
                    class="mr-2 my-1"
                    :image="token.imageUrl"
                />
                <span class="text-truncate token-name">{{ token.name }}</span>
            </a>
            <div v-if="canAddMoreTokens" class="dropdown-divider mx-2 border-dark"></div>
            <a v-if="canAddMoreTokens" class="dropdown-item" :href="$routing.generate('token_create')">
                <font-awesome-icon
                    icon="plus"
                    class="dropdown-link-icon float-left mt-1 mr-2 d-inline"
                />
                {{ $t('navbar.my_token.add_new') }}
            </a>
        </div>
    </li>
    <li
        v-else
        class="nav-item menu-token align-self-center text-lg-left text-center"
        :class="{'active': isTokenRoute}"
    >
        <a :href="$routing.generate('token_create')" class="nav-link d-flex align-items-center">
            <avatar
                size="small"
                type="token"
                img-class="mb-1"
                class="mr-2 tokens-dropdown-avatar"
                :image="defaultTokenAvatar"
            />
            {{ $t('navbar.my_token') }}
        </a>
    </li>
</template>

<script>
import {faPlus} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {library} from '@fortawesome/fontawesome-svg-core';
import Avatar from '../Avatar';
import {VBTooltip} from 'bootstrap-vue';

library.add(faPlus);

export default {
    name: 'NavTokenDropdown',
    directives: {
        'b-tooltip': VBTooltip,
    },
    components: {
        FontAwesomeIcon,
        Avatar,
    },
    props: {
        show: Boolean,
        tokens: Array,
        defaultTokenAvatar: String,
        route: String,
        canAddMoreTokens: Boolean,
    },
    computed: {
        isTokenRoute() {
            return this.route && this.route.startsWith('token');
        },
    },
    methods: {
        toggleDropdown() {
            this.$emit('toggle');
        },
        getTooltip(tokenName) {
            return 12 < tokenName?.length
                ? {
                    title: tokenName,
                    placement: 'right',
                    customClass: 'token-dropdown-item',
                }
                : null;
        },
    },
};
</script>
