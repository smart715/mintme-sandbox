const Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')

    .setPublicPath('/build')

    .addEntry('api', './assets/js/api.js')
    .addEntry('main', './assets/js/main.js')
    .addEntry('home', './assets/js/home.js')
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
    .addEntry('mail', './assets/scss/mail.sass')
    .addEntry('news', './assets/js/news.js')
    .addEntry('edit_post', './assets/js/edit_post.js')
    .addEntry('show_post', './assets/js/show_post.js')

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

module.exports = config;
