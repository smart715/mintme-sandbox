/* eslint-disable */

// This is customized init-swagger-ui.js version
// to include requestInterceptor funciton for adding "Bearer" word to the token

window.onload = () => {
    const data = JSON.parse(document.getElementById('swagger-data').innerText);
    const ui = SwaggerUIBundle({
        spec: data.spec,
        dom_id: '#swagger-ui',
        validatorUrl: null,
        presets: [
            SwaggerUIBundle.presets.apis,
            SwaggerUIStandalonePreset,
        ],
        plugins: [
            SwaggerUIBundle.plugins.DownloadUrl,
        ],
        layout: 'StandaloneLayout',
        requestInterceptor: (req) => {
            if(req.headers.Authorization) {
                req.headers.Authorization = 'Bearer ' + req.headers.Authorization.split(' ').pop();
            }            
            return req;
        },
    });

    window.ui = ui;
};
