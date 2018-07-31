<template>
    <div :class="containerClass">
        <div class="card">
            <div class="card-header">
                Trade History
            </div>
            <div class="card-body p-0">
                <div class="table-responsive fix-height">
                    <table class="table m-0 border-0">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Order maker</th>
                                <th>Order trader</th>
                                <th>Price per token</th>
                                <th>Token amount</th>
                                <th>WEB amount</th>
                                <th>Date & Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Sell</td>
                                <td>Jon Doe 😄</td>
                                <td>Jon Doe 😄</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>26.07.2018 16.25.09</td>
                            </tr>
                            <tr>
                                <td>Sell</td>
                                <td>Jon Doe 😄</td>
                                <td>Jon Doe 😄</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>26.07.2018 16.25.09</td>
                            </tr>
                            <tr>
                                <td>Sell</td>
                                <td>Jon Doe 😄</td>
                                <td>Jon Doe 😄</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>26.07.2018 16.25.09</td>
                            </tr>
                            <tr>
                                <td>Sell</td>
                                <td>Jon Doe 😄</td>
                                <td>Jon Doe 😄</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>26.07.2018 16.25.09</td>
                            </tr>
                            <tr>
                                <td>Sell</td>
                                <td>Jon Doe 😄</td>
                                <td>Jon Doe 😄</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>26.07.2018 16.25.09</td>
                            </tr>
                            <tr>
                                <td>Sell</td>
                                <td>Jon Doe 😄</td>
                                <td>Jon Doe 😄</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>26.07.2018 16.25.09</td>
                            </tr>
                            <tr>
                                <td>Sell</td>
                                <td>Jon Doe 😄</td>
                                <td>Jon Doe 😄</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>26.07.2018 16.25.09</td>
                            </tr>
                            <tr>
                                <td>Sell</td>
                                <td>Jon Doe 😄</td>
                                <td>Jon Doe 😄</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>26.07.2018 16.25.09</td>
                            </tr>
                            <tr>
                                <td>Sell</td>
                                <td>Jon Doe 😄</td>
                                <td>Jon Doe 😄</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>26.07.2018 16.25.09</td>
                            </tr>
                            <tr>
                                <td>Sell</td>
                                <td>Jon Doe 😄</td>
                                <td>Jon Doe 😄</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>771.37300</td>
                                <td>26.07.2018 16.25.09</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
  name: 'TokenInvestTradeHistory',
  props: {
      containerClass: String,
  },
  data() {
    return {};
  },
};
</script>
