import {mount} from '../testHelper';
import CopyLink from '../../js/components/CopyLink';

describe('CopyLink', () => {
    let copyLink = mount(CopyLink, {
        propsData: {contentToCopy: 'foo'},
    });
    it('renders correctly with different props', () => {
        expect(copyLink.contentToCopy).to.equal('foo');

        copyLink = mount(CopyLink, {
            propsData: {contentToCopy: 'bar'},
        });
        expect(copyLink.contentToCopy).to.equal('bar');
    });
    it('triggers on copy success', (done) => {
        copyLink.onCopy('e');
        Vue.nextTick(() => {
            expect(copyLink.tooltipMessage).to.deep.equal('Copied!');
            done();
        });
    });
    it('triggers on copy error', (done) => {
        copyLink.onError('e');
        Vue.nextTick(() => {
            expect(copyLink.tooltipMessage).to.deep.equal('Press Ctrl+C to copy');
            done();
        });
    });
});
