<template>
    <div :class="containerClass">
        <div class="card h-100">
            <div class="card-header text-center">
                Sell Order
                <span class="card-header-icon text-white">
                    <font-awesome-icon
                        icon="question"
                        class="m-0 p-1 h4 bg-orange rounded-circle square"
                    />
                </span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div
                        class="col-12 col-sm-6 col-md-12 col-xl-6
                               pr-0 pb-3 pb-sm-0 pb-md-3 pb-xl-0"
                    >
                        Your Tokens:
                        <span class="text-primary">
                            9999
                            <font-awesome-icon
                                icon="question"
                                class="ml-1 mb-1 bg-primary text-white
                                       rounded-circle square blue-question"
                            />
                        </span>
                    </div>
                    <div
                        class="col-12 col-sm-6 col-md-12 col-xl-6
                               text-sm-right text-md-left text-xl-right"
                        >
                        <label class="custom-control custom-checkbox">
                            <input
                                type="checkbox"
                                id="sell-price"
                                class="custom-control-input"
                            >
                            <label
                                class="custom-control-label"
                                for="sell-price"
                            >
                                Market Price
                                <font-awesome-icon
                                    icon="question"
                                    class="ml-1 mb-1 bg-primary text-white
                                           rounded-circle square blue-question"
                                />
                            </label>
                        </label>
                    </div>
                    <div class="col-12 pt-3">
                        <label
                            for="sell-price-input"
                            class="text-primary"
                        >
                            Price in WEB:
                            <font-awesome-icon
                                icon="question"
                                class="ml-1 mb-1 bg-primary text-white
                                       rounded-circle square blue-question"
                            />
                        </label>
                        <input
                            type="text"
                            id="sell-price-input"
                            class="form-control"
                            :value="price"
                        >
                    </div>
                    <div class="col-12 pt-3">
                        <label
                            for="sell-price-amount"
                            class="text-primary"
                        >
                            Amount:
                        </label>
                        <input
                            type="text"
                            id="sell-price-amount"
                            class="form-control"
                            :value="amount"
                        >
                    </div>
                    <div class="col-12 pt-3">
                        Total Price: {{ totalPrice }} WEB
                        <font-awesome-icon
                            icon="question"
                            class="ml-1 mb-1 bg-primary text-white
                                   rounded-circle square blue-question"
                        />
                    </div>
                    <div class="col-12 pt-4 text-center">
                        <button v-if="loggedIn" class="btn btn-primary">
                            Create sell order
                        </button>
                        <template v-else>
                            <a :href="loginUrl" class="btn btn-primary">Log In</a>
                            <span class="px-2">or</span>
                            <a :href="signupUrl">Sign Up</a>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'TokenTradeSellOrder',
    props: {
        containerClass: String,
        websocketUrl: String,
        loginUrl: String,
        signupUrl: String,
        marketName: String,
        loggedIn: Boolean,
        sell: Object,
    },
    computed: {
        market: function() {
            return JSON.parse(this.marketName);
        },
        amount: function() {
            return this.sell.amount || null;
        },
        price: function() {
            return this.sell.price || null;
        },
        totalPrice: function() {
            return this.amount && this.price ? this.amount * this.price : 0;
        },
    },
    data() {
        return {};
    },
    mounted() {},
};
</script>
