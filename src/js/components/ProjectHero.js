import { gsap } from '@js/lib/gsap';

/**
 * ProjectHero — tilts the project hero's image towards the pointer.
 *
 * The image already rests at an angle (project-hero.scss); moving the pointer
 * anywhere over the hero turns it a little further, up to `data-max-tilt`
 * degrees on each axis, and it eases back when the pointer leaves. The yellow
 * corners sit on a layer in front of the image, so the tilt shows their depth.
 *
 * Desktop only: needs the large breakpoint, a pointer that can hover and
 * motion allowed.
 */

const MAX_TILT = 10; // degrees, when the element has no data-max-tilt
const DURATION = 0.8; // seconds to catch up with the pointer

export default class ProjectHero {
    constructor($el) {
        this.$el = $el;
        this.$tilt = $el.querySelector('.js-projectHeroTilt');
        this.$area = $el.closest('.projectHero') ?? $el;
        this.maxTilt = parseFloat($el.dataset.maxTilt) || MAX_TILT;

        if (!this.$tilt) {
            return;
        }

        this.mm = gsap.matchMedia();
        this.mm.add('(min-width: 1280px) and (hover: hover) and (prefers-reduced-motion: no-preference)', () => this.setup());
    }

    setup() {
        const rotateX = gsap.quickTo(this.$tilt, 'rotationX', { duration: DURATION, ease: 'power3.out' });
        const rotateY = gsap.quickTo(this.$tilt, 'rotationY', { duration: DURATION, ease: 'power3.out' });

        const onMove = (event) => {
            if (event.pointerType === 'touch') {
                return;
            }

            // -1 → 1 across the hero, measured from the image's centre so
            // the image faces the pointer wherever it is.
            const area = this.$area.getBoundingClientRect();
            const media = this.$el.getBoundingClientRect();
            const x = gsap.utils.clamp(-1, 1, (event.clientX - (media.left + media.width / 2)) / (area.width / 2));
            const y = gsap.utils.clamp(-1, 1, (event.clientY - (media.top + media.height / 2)) / (area.height / 2));

            // Positive rotationY sends the right edge back, positive
            // rotationX the top edge: the side nearest the pointer recedes.
            rotateY(x * this.maxTilt);
            rotateX(-y * this.maxTilt);
        };

        const onLeave = () => {
            rotateX(0);
            rotateY(0);
        };

        this.$area.addEventListener('pointermove', onMove);
        this.$area.addEventListener('pointerleave', onLeave);

        return () => {
            this.$area.removeEventListener('pointermove', onMove);
            this.$area.removeEventListener('pointerleave', onLeave);
            gsap.set(this.$tilt, { clearProps: 'transform' });
        };
    }
}
