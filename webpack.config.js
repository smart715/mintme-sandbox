const Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')

    .setPublicPath('/build')

    .addEntry('main', './assets/js/main.js')
    .addEntry('base_main', './assets/js/base_main.js')
    .addEntry('reset', './assets/js/reset.js')
    .addEntry('admin', './assets/js/admin/admin.js')
    .addEntry('concord-bold', './assets/fonts/Concord-Bold.ttf')
    .addEntry('concord', './assets/fonts/Concord.otf')
    .addEntry('footer', './assets/js/footer.js')

    // Entry pages
    .addEntry('pages/home', './assets/js/pages/home.js')
    .addEntry('pages/error', './assets/js/pages/error.js')
    .addEntry('pages/knowledge_base', './assets/js/pages/knowledge_base.js')
    .addEntry('pages/api', './assets/js/pages/api.js')
    .addEntry('pages/phone_verification', './assets/js/pages/phone_verification.js')
    .addEntry('pages/chat', './assets/js/pages/chat.js')
    .addEntry('pages/edit_post', './assets/js/pages/edit_post.js')
    .addEntry('pages/news', './assets/js/pages/news.js')
    .addEntry('pages/pair', './assets/js/pages/pair.js')
    .addEntry('pages/profile', './assets/js/pages/profile.js')
    .addEntry('pages/referral', './assets/js/pages/referral.js')
    .addEntry('pages/register', './assets/js/pages/register.js')
    .addEntry('pages/settings', './assets/js/pages/settings.js')
    .addEntry('pages/show_post', './assets/js/pages/show_post.js')
    .addEntry('pages/token_creation', './assets/js/pages/token_creation.js')
    .addEntry('pages/trading', './assets/js/pages/trading.js')
    .addEntry('pages/wallet', './assets/js/pages/wallet.js')
    .addEntry('pages/login', './assets/js/pages/login.js')
    .addEntry('pages/user_home', './assets/js/pages/user_home.js')
    .addEntry('pages/voting', './assets/js/pages/voting.js')

    .splitEntryChunks()

    .enableSingleRuntimeChunk()

    .enableSourceMaps(!Encore.isProduction())

    .enableVersioning(Encore.isProduction())

    .cleanupOutputBeforeBuild()

    .enableSassLoader()

    .enablePostCssLoader()

    .enableVueLoader(() => {}, {runtimeCompilerBuild: false})

    .configureImageRule({
        filename: 'images/[name].[hash:8].[ext]',
    })

    .configureFontRule({
        type: 'asset',
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

config.resolve.fallback = {crypto: false};

module.exports = config;
