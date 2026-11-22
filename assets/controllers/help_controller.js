import { Controller } from '@hotwired/stimulus';

const SEEN = 'repcoach.help';

export default class extends Controller {
    static targets = ['dialog'];

    connect() {
        if (null === localStorage.getItem(SEEN)) {
            this.open();
        }
    }

    open() {
        this.dialogTarget.show();
        localStorage.setItem(SEEN, 'seen');
    }

    close() {
        this.dialogTarget.close();
    }

    toggle() {
        if (this.dialogTarget.open) {
            this.close();
        } else {
            this.open();
        }
    }
}
