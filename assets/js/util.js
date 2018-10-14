/**
 * function that assign dynamic height to element
 * @param {object} contentElement - element instance.
 * @return {void}
 */
function setContentMinHeight(contentElement) {
    let headerHeight = document.getElementById('header').offsetHeight;
    let footerHeight = document.getElementById('footer').offsetHeight;
    let contentMinHeight = window.innerHeight - headerHeight - footerHeight;
    contentElement.style.minHeight = contentMinHeight + 'px';
}

/**
 * function that called setContentMinHeight(contentElement)
 * on created and resize events
 * @param {object} contentElement - element instance.
 * @return {void}
 */
export function setContentMinHeightDynamically(contentElement) {
    setContentMinHeight(contentElement);
    window.addEventListener('resize', () => {
        setContentMinHeight(contentElement);
    });
}
