var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')

    .setPublicPath('/build')

    .addEntry('main', './assets/js/main.js')

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