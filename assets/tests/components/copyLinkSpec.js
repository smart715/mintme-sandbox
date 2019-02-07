import {mount} from '../testHelper';
import CopyLink from '../../components/CopyLink';

describe('CopyLink', () => {
    let copyLink = mount(CopyLink, {
        propsData: {contentToCopy: 'content1'},
    });
    it('renders correctly with different props', () => {
        expect(copyLink.contentToCopy).to.equal('content1');

        copyLink = mount(CopyLink, {
            propsData: {contentToCopy: 'content2'},
        });
        expect(copyLink.contentToCopy).to.equal('content2');
    });
    it('trigger on copy success', (done) => {
        copyLink.onCopy('e');
        Vue.nextTick(() => {
            expect(copyLink.tooltipMessage).to.deep.equal('Copied!');
            done();
        });
    });
    it('trigger on copy error', (done) => {
        copyLink.onError('e');
        Vue.nextTick(() => {
            expect(copyLink.tooltipMessage).to.deep.equal('Press Ctrl+C to copy');
            done();
        });
    });
});
