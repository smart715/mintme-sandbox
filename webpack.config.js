const Encore = require('@symfony/webpack-encore');
const webpack = require('webpack');
const UglifyJsPlugin = require('uglifyjs-webpack-plugin');

Encore
    .setOutputPath('public/build/')

    .setPublicPath('/build')

    .addEntry('api', './assets/js/api.js')
    .addEntry('main', './assets/js/main.js')
    .addEntry('base_main', './assets/js/base_main.js')
    .addEntry('trading', './assets/js/trading.js')
    .addEntry('profile', './assets/js/profile.js')
    .addEntry('wallet', './assets/js/wallet.js')
    .addEntry('pair', './assets/js/pair.js')
    .addEntry('referral', './assets/js/referral.js')
    .addEntry('register', './assets/js/register.js')
    .addEntry('reset', './assets/js/reset.js')
    .addEntry('login', './assets/js/login.js')
    .addEntry('token_creation', './assets/js/token_creation.js')
    .addEntry('settings', './assets/js/settings.js')
    .addEntry('admin', './assets/js/admin/admin.js')
    .addEntry('concord-bold', './assets/fonts/Concord-Bold.ttf')
    .addEntry('concord', './assets/fonts/Concord.otf')
    .addEntry('news', './assets/js/news.js')
    .addEntry('edit_post', './assets/js/edit_post.js')
    .addEntry('show_post', './assets/js/show_post.js')
    .addEntry('chat', './assets/js/chat.js')
    .addEntry('home', './assets/js/home.js')
    .addEntry('footer', './assets/js/footer.js')
    .addEntry('phone_verification', './assets/js/phone_verification.js')

    .enablePostCssLoader()

    .enableSourceMaps(!Encore.isProduction())

    .enableVersioning(Encore.isProduction())

    .cleanupOutputBeforeBuild()

    .enableSassLoader()

    .enableVueLoader(() => {}, {runtimeCompilerBuild: false})

    .configureFilenames({
        'images': 'images/[name].[hash:8].[ext]',
    })

    .addExternals({
        gapi: 'gapi',
        FB: 'FB',
    })
;

// export the final configuration
let config = Encore.getWebpackConfig();
config.resolve.alias['vue$'] = Encore.isProduction()
    ? 'vue/dist/vue.min.js'
    : 'vue/dist/vue.js';

if (Encore.isProduction()) {
    // Remove the old UglifyJs version
    config.plugins = config.plugins.filter(
        (plugin) => !(plugin instanceof webpack.optimize.UglifyJsPlugin)
    );

    // Add the new one
    config.plugins.push(new UglifyJsPlugin());
}

module.exports = config;
