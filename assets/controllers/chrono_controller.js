import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = { seconds: Number, running: Boolean };

    connect() {
        this.sync();
    }

    disconnect() {
        clearInterval(this.timer);
    }

    secondsValueChanged() {
        this.sync();
    }

    runningValueChanged() {
        this.sync();
    }

    sync() {
        clearInterval(this.timer);
        this.startedAt = Date.now() - this.secondsValue * 1000;
        this.render();

        if (this.runningValue) {
            this.timer = setInterval(() => this.render(), 1000);
        }
    }

    render() {
        const elapsed = this.runningValue ? Math.floor((Date.now() - this.startedAt) / 1000) : this.secondsValue;
        const hours = Math.floor(elapsed / 3600);
        const minutes = String(Math.floor(elapsed / 60) % 60).padStart(2, '0');
        const seconds = String(elapsed % 60).padStart(2, '0');

        this.element.textContent = hours > 0 ? `${hours}:${minutes}:${seconds}` : `${minutes}:${seconds}`;
    }
}
