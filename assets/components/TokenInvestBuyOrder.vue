<template>
    <div :class="containerClass">
        <div class="card">
            <div class="card-header text-center">
                Buy Order
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col">
                        Your WEB: <span class="text-primary">9999</span>
                    </div>
                    <div class="col">
                        <input
                            id="buy-price"
                            class="align-middle"
                            type="checkbox"
                        >
                        <label for="buy-price">
                            Market Price
                        </label>
                    </div>
                    <div class="col-12 pt-3">
                        <label
                            for="buy-price-input"
                            class="text-primary"
                        >
                            Price in WEB:
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
export default {
  name: 'TokenInvestBuyOrder',
  props: {
      containerClass: String,
  },
  data() {
    return {};
  },
};
</script>
