<template>
    <div :class="containerClass">
        <div class="card h-100">
            <div class="card-header text-center">
                Buy Order
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
                        Your WEB:
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
                                id="buy-price"
                                class="custom-control-input"
                            >
                            <label
                                class="custom-control-label"
                                for="buy-price"
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
                            for="buy-price-input"
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
                            id="buy-price-input"
                            class="form-control"
                        >
                    </div>
                    <div class="col-12 pt-3">
                        <label
                            for="buy-price-amount"
                            class="text-primary"
                        >
                            Amount:
                        </label>
                        <input
                            type="text"
                            id="buy-price-amount"
                            class="form-control"
                        >
                    </div>
                    <div class="col-12 pt-3">
                        Total Price: 999 WEB
                        <font-awesome-icon
                            icon="question"
                            class="ml-1 mb-1 bg-primary text-white
                                   rounded-circle square blue-question"
                        />
                    </div>
                    <div class="col-12 pt-4 text-center">
                        <button v-if="loggedIn" class="btn btn-primary">
                            Create buy order
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
  name: 'TokenTradeBuyOrder',
  props: {
      containerClass: String,
      websocketUrl: String,
      loginUrl: String,
      signupUrl: String,
      marketName: String,
      loggedIn: Boolean,
  },
  computed: {
      market: function() {
          return JSON.parse(this.marketName);
      }
  },
  data() {
      return {};
  },
  mounted() {},
};
</script>
