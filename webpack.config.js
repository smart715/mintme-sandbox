var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')

    .setPublicPath('/build')

    .addEntry('main', './assets/js/main.js')
    .addEntry('home', './assets/js/home.js')
    .addEntry('trading', './assets/js/trading.js')
    .addEntry('profile', './assets/js/profile.js')
    .addEntry('wallet', './assets/js/wallet.js')
    .addEntry('token', './assets/js/token.js')
    // this script's purpose is solely to "touch" images so that webpack
    // will "notice" them and include to the build.
    .addEntry('assets', './assets/js/assets.js')

    .enablePostCssLoader()

    .enableSourceMaps(!Encore.isProduction())

    .enableVersioning(Encore.isProduction())

    .cleanupOutputBeforeBuild()

    .enableSassLoader()

    .enableVueLoader()

    .configureFilenames({
        'images': 'images/[name].[hash:8].[ext]'
    })
;

// export the final configuration
let config = Encore.getWebpackConfig();
config.resolve.alias['vue$'] = Encore.isProduction() ? 'vue/dist/vue.min.js' : 'vue/dist/vue.js';

module.exports = config;
