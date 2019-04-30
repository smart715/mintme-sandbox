const Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')

    .setPublicPath('/build')

    .addEntry('main', './assets/js/main.js')
    .addEntry('home', './assets/js/home.js')
    .addEntry('trading', './assets/js/trading.js')
    .addEntry('profile', './assets/js/profile.js')
    .addEntry('wallet', './assets/js/wallet.js')
    .addEntry('pair', './assets/js/pair.js')
    .addEntry('referral', './assets/js/referral.js')
    .addEntry('register', './assets/js/register.js')
    .addEntry('reset', './assets/js/reset.js')
    .addEntry('token_creation', './assets/js/token_creation.js')
    .addEntry('settings', './assets/js/settings.js')
    .addEntry('profile_creation', './assets/js/profile_creation.js')
    .addEntry('admin', './assets/js/admin/admin.js')
    .addEntry('jquery-password', './assets/js/admin/jquery.jquery-password-generator-plugin.min.js')


    .enablePostCssLoader()

    .enableSourceMaps(!Encore.isProduction())

    .enableVersioning(Encore.isProduction())

    .cleanupOutputBeforeBuild()

    .enableSassLoader()

    .enableVueLoader()

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

module.exports = config;
