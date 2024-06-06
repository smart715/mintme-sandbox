<template>
    <ul class="token-settings-sidenav">
        <li v-for="tab in tabs" :key="tab.id">
            <a
                v-if="!tab.disabled"
                :href="tab.url"
                class="p-3 py-1 my-1 d-flex align-items-center justify-content-between c-pointer"
                :class="{'nav-active': tab.active}"
                @click="onItemClick(tab, null, $event)"
            >
                <div class="d-flex align-items-center">
                    <font-awesome-icon v-if="tab.icon" :icon="tab.icon" class="mr-3" transform="up-1" />
                    {{ tab.name }}
                </div>
                <font-awesome-icon
                    v-if="tab.tabs"
                    icon="angle-down"
                    class="mr-2"
                    :class="{'caret-opened': tab.opened}"
                    transform="up-1.5"
                />
            </a>
            <ul v-if="!tab.disabled && tab.tabs" :class="{'d-none': !tab.opened}">
                <li v-for="subTab in tab.tabs" :key="subTab.id">
                    <a
                        v-if="!subTab.disabled"
                        :href="subTab.url"
                        class="p-3 py-1 my-1 d-flex align-items-center c-pointer"
                        :class="{'nav-active': subTab.active}"
                        @click="onItemClick(tab, subTab, $event)"
                    >
                        <font-awesome-icon v-if="subTab.icon" :icon="subTab.icon" class="mr-3" transform="up-1" />
                        {{ subTab.name }}
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</template>

<script>
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {faAngleDown} from '@fortawesome/free-solid-svg-icons';
import {library} from '@fortawesome/fontawesome-svg-core';

library.add(faAngleDown);

export default {
    name: 'TokenSettingsNav',
    props: {
        tabs: Array,
    },
    components: {
        FontAwesomeIcon,
    },
    methods: {
        onItemClick(tab, subTab, event) {
            event.preventDefault();

            if (tab.tabs && !subTab) {
                this.$set(tab, 'opened', !tab.opened);
                this.$forceUpdate();

                return;
            }

            this.$emit('change', {tab: tab.id, subTab: subTab?.id});
        },
    },
};
</script>
