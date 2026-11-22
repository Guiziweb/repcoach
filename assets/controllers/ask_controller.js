import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'text', 'typing', 'screen'];
    static outlets = ['figure'];
    static values = { failed: String, slow: String };
    static classes = ['busy', 'open'];

    send(event) {
        if (this.waiting) {
            event.preventDefault();

            return;
        }

        this.waiting = true;
        this.screenTargets.forEach((screen) => {
            screen.value = location.pathname + location.search;
        });
    }

    start() {
        this.unanswered = true;
        this.close();
        this.element.classList.add(this.busyClass);
        this.textTarget.replaceChildren(this.typingTarget.content.cloneNode(true));
        this.figureOutlet.poseValue = 'think';
    }

    open() {
        this.element.classList.add(this.openClass);
    }

    close() {
        this.element.classList.remove(this.openClass);
    }

    dismiss(event) {
        if (!this.element.contains(event.target)) {
            this.close();
        }
    }

    guard(event) {
        const { fetchResponse } = event.detail;

        if (fetchResponse.succeeded) {
            return;
        }

        event.preventDefault();
        this.say(429 === fetchResponse.statusCode ? this.slowValue : this.failedValue);
    }

    end(event) {
        this.waiting = false;
        this.element.classList.remove(this.busyClass);
        this.figureOutlet.poseValue = 'ready';

        if (event.detail.success) {
            this.inputTarget.value = '';
        } else if (this.unanswered) {
            this.say(this.failedValue);
        }

        this.open();
    }

    say(text) {
        this.unanswered = false;
        this.textTarget.textContent = text;
    }
}
