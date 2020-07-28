import {mount} from '../testHelper';
import CopyLink from '../../js/components/CopyLink';

describe('CopyLink', () => {
    let copyLink = mount(CopyLink, {
        propsData: {contentToCopy: 'foo'},
    });
    it('renders correctly with different props', () => {
        expect(copyLink.contentToCopy).toBe('foo');

        copyLink = mount(CopyLink, {
            propsData: {contentToCopy: 'bar'},
        });
        expect(copyLink.contentToCopy).toBe('bar');
    });
    it('triggers on copy success', () => {
        copyLink.onCopy('e');
        expect(copyLink.tooltipMessage).toBe('Copied!');
    });
    it('triggers on copy error', () => {
        copyLink.onError('e');
        expect(copyLink.tooltipMessage).toBe('Press Ctrl+C to copy');
    });
});
