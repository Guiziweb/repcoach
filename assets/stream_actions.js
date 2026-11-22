import { StreamActions, visit } from '@hotwired/turbo';

StreamActions.visit = function () {
    visit(this.getAttribute('url'), { frame: this.getAttribute('frame') });
};
