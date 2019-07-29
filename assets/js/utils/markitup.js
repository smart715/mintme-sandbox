import markitup from 'markitup';
import markitupSet from '../markitup.js';

/**
 * apply markitup plugin to textarea
 * @param {mixed} textarea
 */
function useMarkitup(textarea) {
    markitup(textarea, markitupSet);
}

export {
    useMarkitup,
};
