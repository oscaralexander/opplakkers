import { ScrollTrigger, reducedMotion } from '@js/lib/gsap';

/**
 * Equinox — exposes an element's progress through the viewport as CSS custom
 * properties, so parallax and reveal effects can be written in the stylesheet:
 *
 *   --equinox-pos   0 as the element enters at the bottom → 1 as it leaves
 *                   at the top
 *   --equinox       the same, remapped to 1 → -1 (0 at the centre)
 *   --equinox-abs   |--equinox|
 *
 * It also toggles .is-visible while the element is on screen, adds
 * .is-visibleOnce the first time, and fires an `equinox:toggle` event.
 *
 * ScrollTrigger does the work: it batches every instance's measurements into
 * one pass per scroll frame, and re-measures on resize and when late assets
 * settle the layout — none of which a hand-rolled scroll listener gets free.
 *
 * With reduced motion on, the properties stay at their rest values (0) so a
 * stylesheet that multiplies them simply gets no movement.
 */
export default class Equinox {
    constructor($el) {
        this.$el = $el;

        if (reducedMotion.matches) {
            return;
        }

        this.trigger = ScrollTrigger.create({
            trigger: $el,
            once: $el.hasAttribute('data-equinox-once'),
            onToggle: (self) => {
                $el.classList.toggle('is-visible', self.isActive);

                if (self.isActive) {
                    $el.classList.add('is-visibleOnce');
                }

                $el.dispatchEvent(new CustomEvent('equinox:toggle', { detail: { isVisible: self.isActive } }));
            },
            onUpdate: (self) => this.update(self.progress),
        });

        this.update(this.trigger.progress);
    }

    update(progress) {
        const equinox = 1 - progress * 2;

        this.$el.style.setProperty('--equinox-pos', progress.toFixed(4));
        this.$el.style.setProperty('--equinox', equinox.toFixed(4));
        this.$el.style.setProperty('--equinox-abs', Math.abs(equinox).toFixed(4));
    }
}
