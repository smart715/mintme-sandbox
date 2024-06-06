export default {
    hashFormat: function(hashes) {
        let result = 0;

        if (0 <= hashes && 1000 > hashes) {
            result = hashes;

            return `${result.toFixed(2)} H`;
        }

        if (1000 <= hashes && hashes < Math.pow(1000, 2)) {
            result = hashes / 1000;

            return `${result.toFixed(2)} KH`;
        }

        if (hashes >= Math.pow(1000, 2) && hashes < Math.pow(1000, 3)) {
            result = hashes / Math.pow(1000, 2);

            return `${result.toFixed(2)} MH`;
        }

        if (hashes >= Math.pow(1000, 3) && hashes < Math.pow(1000, 4)) {
            result = hashes / Math.pow(1000, 3);

            return `${result.toFixed(2)} GH`;
        }

        if (hashes >= Math.pow(1000, 4)) {
            result = hashes / Math.pow(1000, 4);

            return `${result.toFixed(2)} TH`;
        }
    },
};
