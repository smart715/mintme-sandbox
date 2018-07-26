// Require all images so that webpack will handle them and add to manifest.json,
// as well as move them to public/build/,
// so that later Asset component will be able to use asset versioning
// based on the manifest.json (see assets section in config/packages/framework.yaml),
// and Twig could properly use asset() function for images.
const imagesContext = require.context('../img', false, /\.(png|jpg|jpeg|gif|ico|svg)$/);
imagesContext.keys().forEach(imagesContext);
