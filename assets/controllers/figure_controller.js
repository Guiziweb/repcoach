import { Controller } from '@hotwired/stimulus';
import { figure } from '../figure.js';

const SCALE = 0.9;
const TICK = 0.03;

export default class extends Controller {
    static values = { pose: { type: String, default: 'ready' } };

    connect() {
        this.time = 0;
        this.still = matchMedia('(prefers-reduced-motion: reduce)').matches;
        this.draw();
    }

    disconnect() {
        cancelAnimationFrame(this.frame);
    }

    poseValueChanged() {
        if (this.still) {
            this.draw();
        }
    }

    draw() {
        this.element.innerHTML = figure(this.poseValue, this.time, SCALE);

        if (!this.still) {
            this.time += TICK;
            this.frame = requestAnimationFrame(() => this.draw());
        }
    }
}
