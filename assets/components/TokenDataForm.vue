<template>
    <form action="" id="token-data-form">
        <div class="row">
            <div class="col-lg-6">
                <div class="form-group">
                    <label for="name" class="mb-0">Token name</label>
                    <input
                        name="name"
                        type="text"
                        class="form-control"
                        id="name"/>
                </div>
                <div class="form-group">
                    <label for="website" class="mb-0">Website address:</label>
                    <input
                        name="website"
                        type="url"
                        class="form-control"
                        id="website"/>
                </div>
                <div class="form-group">
                    <div class="row" v-if="!showFacebookFormInput">
                        <div class="col-lg-5 col-md-6">
                            <a
                                class="btn btn-primary custom-btn
                                d-inline-flex align-items-center"
                                @click="showFacebookFormInput = true">
                                <font-awesome-icon
                                    :icon="['fab', 'facebook']" />
                                Change Facebook
                            </a>
                        </div>
                    </div>
                    <div  v-else-if="showFacebookFormInput">
                        <label for="lname" class="mb-0">
                            Facebook address:
                        </label>
                        <input
                            name="facebook"
                            type="url"
                            class="form-control"
                            id="facebook"/>
                    </div>
                </div>
                <div class="form-group">
                    <div class="row" v-if="!showYoutubeFormInput">
                        <div class="col-lg-5 col-md-6">
                            <a
                                class="btn btn-primary custom-btn
                                d-inline-flex align-items-center "
                                @click="showYoutubeFormInput = true">
                                <font-awesome-icon :icon="['fab', 'youtube']" />
                                Change YouTube
                            </a>
                        </div>
                    </div>
                    <div  v-else-if="showYoutubeFormInput">
                        <label for="youtube" class="mb-0">
                            YouTube address:
                        </label>
                        <input
                            name="youtube"
                            type="url"
                            class="form-control"
                            id="youtube"/>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="form-group">
                    <label for="desc" class="mb-0">Description:</label>
                    <textarea
                        name="desc"
                        class="form-control"
                        rows="5" id="tokendesc">
                    </textarea>
                </div>
                <div class="form-group">
                    <label for="lockin" class="mb-0">Lock-in:</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <font-awesome-icon icon="unlock" />
                            </span>
                        </div>
                        <range-slider
                            class="slider form-control-range mt-2"
                            min="10"
                            max="100"
                            step="1"
                            v-model="sliderValue">
                        </range-slider>
                        <div class="input-group-append">
                            <span class="input-group-text">
                                <font-awesome-icon icon="lock" />
                            </span>
                        </div>
                    </div>

                    <span>{{ sliderValue }}%</span>
                </div>
                <p class="text-right">
                    Short description,...
                </p>
                <button
                    type="submit"
                    class="btn btn-primary float-right">
                    Save
                </button>
            </div>
        </div>
    </form>
</template>

<script>
import RangeSlider from 'vue-range-slider';
import {library} from '@fortawesome/fontawesome-svg-core';
import {faUnlock, faLock} from '@fortawesome/free-solid-svg-icons';
import {faFacebook, faYoutube} from '@fortawesome/free-brands-svg-icons';
import {FontAwesomeIcon, FontAwesomeLayers}
    from '@fortawesome/vue-fontawesome';
library.add(faUnlock, faLock, faFacebook, faYoutube);
export default {
    name: 'TokenNewForm',
    components: {
        FontAwesomeIcon,
        FontAwesomeLayers,
        RangeSlider,
    },
    props: {
        formStatus: String,
    },
    data() {
        return {
            showFacebookFormInput: true,
            showYoutubeFormInput: true,
            sliderValue: 50,
        };
    },
    created: function() {
        if (this.formStatus == 'edit') {
            this.showFacebookFormInput = false;
            this.showYoutubeFormInput = false;
        } else {
            this.showFacebookFormInput = true;
            this.showYoutubeFormInput = true;
        }
    },
};
</script>
