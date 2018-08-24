<template>
    <div :class="containerClass">
        <div class="card">
            <div class="card-header">
                Jon Doe
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <a href="#" target="_blank">
                            linktotokencreatorprofile.com
                        </a>
                        <div class="pt-4">
                            <div class="pb-1">
                                Facebook:
                                <a href="#" target="_blank">
                                    linktofacebookprofile.com
                                </a>
                                <font-awesome-icon
                                    icon="question"
                                    class="ml-1 mb-1 bg-blue text-white
                                           rounded-circle square blue-question"
                                />
                            </div>
                            <div class="pb-1">
                                YouTube:
                                <a href="#" target="_blank">
                                    linktoyoutubeprofile.com
                                </a>
                                <font-awesome-icon
                                    icon="question"
                                    class="ml-1 mb-1 bg-blue text-white
                                           rounded-circle square blue-question"
                                />
                            </div>
                            <div>
                                Web:
                                <a href="#" target="_blank">
                                    linktowebpage.com
                                </a>
                                <font-awesome-icon
                                    icon="question"
                                    class="ml-1 mb-1 bg-blue text-white
                                           rounded-circle square blue-question"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="col-12 pt-3 text-right">
                        <b-dropdown id="share" text="Share" variant="primary">
                            <b-dropdown-item
                                href="#"
                                target="_blank"
                            >
                                Facebook
                            </b-dropdown-item>
                            <b-dropdown-item
                                href="#"
                                target="_blank"
                            >
                                LinkedIn
                            </b-dropdown-item>
                        </b-dropdown>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import bDropdown from 'bootstrap-vue/es/components/dropdown/dropdown';
import bDropdownItem from 'bootstrap-vue/es/components/dropdown/dropdown-item';

export default {
    name: 'TokenIntroductionProfile',
    props: {
        containerClass: String,
    },
    data() {
        return {};
    },
    components: {
        bDropdown,
        bDropdownItem,
    },
};
</script>
