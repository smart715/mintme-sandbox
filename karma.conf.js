const context = require('@symfony/webpack-encore/lib/context');
const parseRuntime = require('@symfony/webpack-encore/lib/config/parse-runtime');
const encoreArgs = ['dev-server'];

context.runtimeConfig = parseRuntime(
    require('yargs').parse(encoreArgs),
    process.cwd()
);

const webpackConfig = require('./webpack.config');

module.exports = function(config) {
  config.set({
    basePath: '',
    frameworks: ['mocha', 'requirejs', 'chai', 'es6-shim'],
    files: [
      'node_modules/babel-polyfill/dist/polyfill.js',
      'test-main.js',
      {pattern: 'assets/tests/**/*Spec.js', included: false},
      {pattern: 'assets/js/*.js', included: false},
    ],
    webpack: webpackConfig,
    webpackServer: {
        noInfo: true,
    },
    webpackMiddleware: {
        noInfo: true,
    },
    preprocessors: {
        'assets/tests/**/*Spec.js': ['webpack'],
        'assets/js/**/*.js': ['webpack', 'coverage'],
        'assets/components/**/*.vue': ['webpack', 'coverage'],
    },
    coverageReporter: {
      type: 'text-summary',
      dir: 'coverage/',
      includeAllSources: true,
    },
    reporters: ['progress', 'coverage'],
    port: 9876,
    client: {
      captureConsole: false,
    },
    colors: true,
    logLevel: config.LOG_INFO,
    autoWatch: false,
    browsers: ['PhantomJS'],
    singleRun: true,
    concurrency: Infinity,
  });
};
