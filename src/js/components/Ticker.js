import { gsap, reducedMotion } from '@js/lib/gsap';

/**
 * Ticker — a row of items that scrolls past continuously and can be dragged.
 *
 * The original items are cloned until the row is wider than its container
 * plus one full set, and the row is translated with a modulo wrap: once it has
 * moved a whole set's width it snaps back by exactly that amount, which is
 * invisible because the next set is identical. No DOM is moved per frame.
 *
 * The loop runs on GSAP's shared ticker and is removed entirely while the
 * ticker is off screen, so a page with several tickers pays only for the ones
 * in view. Dragging retargets the position; the row eases toward it, so a
 * release glides rather than stops dead.
 *
 * With reduced motion on, the row stands still but remains draggable.
 */

const INERTIA = 0.1; // fraction of the remaining distance covered per frame
const FRAME_MS = 1000 / 60;

export default class Ticker {
    constructor($el) {
        this.$el = $el;
        this.$inner = $el.querySelector('.js-tickerInner');

        if (!this.$inner || !this.$inner.children.length) {
            return;
        }

        this.$$items = Array.from(this.$inner.children);
        this.direction = $el.dataset.tickerDirection === 'right' ? 1 : -1;
        // px per frame at 60fps, as the data attribute has always meant.
        this.speed = reducedMotion.matches ? 0 : parseFloat($el.dataset.tickerSpeed ?? '0.5');
        this.speedFactor = 1;
        this.pauseOnHover = $el.dataset.tickerPauseOnHover !== 'false';

        this.x = 0;
        this.targetX = 0;
        this.setWidth = 0;
        this.dragging = false;
        this.dragStartX = 0;
        this.dragStartTarget = 0;
        this.running = false;

        this.tick = this.tick.bind(this);
        this.onPointerMove = this.onPointerMove.bind(this);
        this.onPointerUp = this.onPointerUp.bind(this);

        this.build();
        this.initListeners();
    }

    /**
     * Measures one set of items and adds enough clones to cover the container
     * with a full set to spare. Idempotent: clones from a previous build are
     * dropped first.
     */
    build() {
        this.$inner.querySelectorAll('[data-ticker-clone]').forEach(($clone) => $clone.remove());

        this.setWidth = this.$inner.scrollWidth;

        if (this.setWidth === 0) {
            return;
        }

        const needed = this.$el.offsetWidth + this.setWidth;
        const copies = Math.ceil(needed / this.setWidth) - 1;
        const fragment = document.createDocumentFragment();

        for (let i = 0; i < copies; i++) {
            this.$$items.forEach(($item) => {
                const $clone = $item.cloneNode(true);
                $clone.setAttribute('aria-hidden', 'true');
                $clone.dataset.tickerClone = '';
                $clone.querySelectorAll('a, button, [tabindex]').forEach(($focusable) => {
                    $focusable.setAttribute('tabindex', '-1');
                });
                fragment.append($clone);
            });
        }

        this.$inner.append(fragment);
        this.render();
    }

    initListeners() {
        new IntersectionObserver(([entry]) => this.setRunning(entry.isIntersecting)).observe(this.$el);

        let frame = null;
        new ResizeObserver(() => {
            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(() => this.build());
        }).observe(this.$el);

        window.addEventListener('load', () => this.build(), { once: true });

        this.$el.addEventListener('pointerdown', (event) => this.onPointerDown(event));

        if (this.pauseOnHover) {
            this.$el.addEventListener('pointerenter', () => this.ease(0));
            this.$el.addEventListener('pointerleave', () => this.ease(1));
        }
    }

    setRunning(running) {
        if (running === this.running) {
            return;
        }

        this.running = running;

        if (running) {
            gsap.ticker.add(this.tick);
        } else {
            gsap.ticker.remove(this.tick);
        }
    }

    /** Eases the auto-scroll speed toward a factor of its base speed. */
    ease(factor) {
        gsap.to(this, { duration: 0.8, ease: 'power2.out', speedFactor: factor });
    }

    tick(time, deltaTime) {
        if (this.setWidth === 0) {
            return;
        }

        if (!this.dragging) {
            this.targetX += this.direction * this.speed * this.speedFactor * (deltaTime / FRAME_MS);
        }

        this.x += (this.targetX - this.x) * INERTIA;

        // Keep x within one set's width of the origin. Shifting the target by
        // the same amount keeps the easing continuous across the wrap.
        const wrapped = ((this.x % this.setWidth) + this.setWidth) % this.setWidth - this.setWidth;
        const shift = wrapped - this.x;

        if (shift !== 0) {
            this.x = wrapped;
            this.targetX += shift;
            this.dragStartTarget += shift;
        }

        this.render();
    }

    render() {
        this.$inner.style.transform = `translate3d(${this.x.toFixed(2)}px, 0, 0)`;
    }

    onPointerDown(event) {
        if (event.button !== 0 && event.pointerType === 'mouse') {
            return;
        }

        this.dragging = true;
        this.dragStartX = event.clientX;
        this.dragStartTarget = this.targetX;
        this.$el.classList.add('is-grabbing');
        this.$el.setPointerCapture(event.pointerId);

        this.$el.addEventListener('pointermove', this.onPointerMove);
        this.$el.addEventListener('pointerup', this.onPointerUp);
        this.$el.addEventListener('pointercancel', this.onPointerUp);
    }

    onPointerMove(event) {
        this.targetX = this.dragStartTarget + (event.clientX - this.dragStartX);
    }

    onPointerUp(event) {
        this.dragging = false;
        this.$el.classList.remove('is-grabbing');
        this.$el.releasePointerCapture(event.pointerId);

        this.$el.removeEventListener('pointermove', this.onPointerMove);
        this.$el.removeEventListener('pointerup', this.onPointerUp);
        this.$el.removeEventListener('pointercancel', this.onPointerUp);
    }
}
