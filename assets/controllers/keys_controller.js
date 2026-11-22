import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['previous', 'next'];

    previous(event) {
        this.follow(event, this.hasPreviousTarget ? this.previousTarget : null);
    }

    next(event) {
        this.follow(event, this.hasNextTarget ? this.nextTarget : null);
    }

    follow(event, link) {
        if (event.target instanceof HTMLInputElement || null === link) {
            return;
        }

        link.click();
    }
}
