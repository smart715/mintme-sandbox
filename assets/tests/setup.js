import failOnConsole from 'jest-fail-on-console';

failOnConsole({
    shouldFailOnAssert: false,
    shouldFailOnInfo: false,
    shouldFailOnDebug: false,
    shouldFailOnLog: false,
    shouldFailOnError: true,
    shouldFailOnWarn: true,
});
