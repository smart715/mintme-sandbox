<template>
    <div :class="containerClass">
        <div class="card h-100">
            <div class="card-header text-center">
                Buy Order
                <guide>
                    <template slot="header">
                        Buy Order Guide
                    </template>
                    <template slot="body">
                        Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                    </template>
                </guide>
            </div>
            <div class="card-body">
                <div class="row">
                    <div
                        class="col-12 col-sm-6 col-md-12 col-xl-6
                        pr-0 pb-3 pb-sm-0 pb-md-3 pb-xl-0">
                        Your WEB:
                        <span class="text-primary">
                            9999
                            <guide>
                                <font-awesome-icon
                                    icon="question"
                                    slot='icon'
                                    class="ml-1 mb-1 bg-primary text-white
                                            rounded-circle square blue-question"/>
                                <template slot="header">
                                    Your WEB Guide
                                </template>
                                <template slot="body">
                                    Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                                </template>
                            </guide>
                        </span>
                    </div>
                    <div
                        class="col-12 col-sm-6 col-md-12 col-xl-6
                        text-sm-right text-md-left text-xl-right">
                        <label class="custom-control custom-checkbox">
                            <input
                                type="checkbox"
                                id="buy-price"
                                class="custom-control-input">
                            <label
                                class="custom-control-label"
                                for="buy-price">
                                Market Price
                                <guide>
                                    <font-awesome-icon
                                        icon="question"
                                        slot='icon'
                                        class="ml-1 mb-1 bg-primary text-white
                                            rounded-circle square blue-question"/>
                                    <template slot="header">
                                        Market Price Guide
                                    </template>
                                    <template slot="body">
                                        Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                                    </template>
                                </guide>
                            </label>
                        </label>
                    </div>
                    <div class="col-12 pt-3">
                        <label
                            for="buy-price-input"
                            class="text-primary">
                            Price in WEB:
                            <guide>
                                <font-awesome-icon
                                    icon="question"
                                    slot='icon'
                                    class="ml-1 mb-1 bg-primary text-white
                                            rounded-circle square blue-question"/>
                                <template slot="header">
                                    Price in WEB Guide
                                </template>
                                <template slot="body">
                                    Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                                </template>
                            </guide>
                        </label>
                        <input
                            type="text"
                            id="buy-price-input"
                            class="form-control">
                    </div>
                    <div class="col-12 pt-3">
                        <label
                            for="buy-price-amount"
                            class="text-primary">
                            Amount:
                        </label>
                        <input
                            type="text"
                            id="buy-price-amount"
                            class="form-control">
                    </div>
                    <div class="col-12 pt-3">
                        Total Price: 999 WEB
                        <guide>
                            <font-awesome-icon
                                icon="question"
                                slot='icon'
                                class="ml-1 mb-1 bg-primary text-white
                                            rounded-circle square blue-question"/>
                            <template slot="header">
                                Total Price Guide
                            </template>
                            <template slot="body">
                                Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                            </template>
                        </guide>
                    </div>
                    <div class="col-12 pt-4 text-center">
                        <button class="btn btn-primary">
                            Create buy order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Guide from '../../Guide';
export default {
    name: 'TokenTradeBuyOrder',
    components: {
        Guide,
    },
    props: {
        containerClass: String,
    },
    data() {
        return {};
    },
};
</script>
