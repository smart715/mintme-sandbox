<template>
    <div :class="containerClass">
        <div class="card">
            <div class="card-header text-center">
                Sell Order
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col">
                        Your WEB: <span class="text-primary">9999</span>
                    </div>
                    <div class="col text-right">
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
                            </label>
                        </label>
                    </div>
                    <div class="col-12 pt-3">
                        <label
                            for="sell-price-input"
                            class="text-primary"
                        >
                            Price in WEB:
                        </label>
                        <input
                            type="text"
                            id="sell-price-input"
                            class="form-control"
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
                        >
                    </div>
                    <div class="col-12 pt-3">
                        Total Price: 999 WEB
                    </div>
                    <div class="col-12 pt-4 text-center">
                        <button class="btn btn-primary">
                            Create sell order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
  name: 'TokenInvestSellOrder',
  props: {
      containerClass: String,
  },
  data() {
    return {};
  },
};
</script>
