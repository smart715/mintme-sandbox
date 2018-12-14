const Encore = require('@symfony/webpack-encore');
const OfflinePlugin = require('offline-plugin');

Encore
    .setOutputPath('public/build/')

    .setPublicPath('/build')

    .addEntry('main', './assets/js/main.js')
    .addEntry('home', './assets/js/home.js')
    .addEntry('trading', './assets/js/trading.js')
    .addEntry('profile', './assets/js/profile.js')
    .addEntry('wallet', './assets/js/wallet.js')
    .addEntry('token', './assets/js/token.js')

    .enablePostCssLoader()

    .enableSourceMaps(!Encore.isProduction())

    .enableVersioning(Encore.isProduction())

    .cleanupOutputBeforeBuild()

    .enableSassLoader()

    .enableVueLoader()

    .configureFilenames({
        'images': 'images/[name].[hash:8].[ext]',
    })

    .addPlugin(new OfflinePlugin())

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

module.exports = config;
