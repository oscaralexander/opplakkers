import { gsap, reducedMotion } from '@js/lib/gsap';

/**
 * Service — tilts a service card in 3D towards the cursor.
 *
 * The edge or corner nearest the cursor is pushed back in depth, up to
 * MAX_TILT degrees on each axis, and the card eases flat again when the
 * cursor leaves. Only mouse and pen pointers tilt it; touch scrolls as usual.
 *
 * With reduced motion on, the card stays flat.
 */

const MAX_TILT = 15; // degrees
const PERSPECTIVE = 800; // px

export default class Service {
    constructor($el) {
        if (reducedMotion.matches) {
            return;
        }

        this.$el = $el;
        // Measured and listened to instead of the card: the card's own box
        // changes as it tilts, which would feed back into the tilt.
        this.$area = $el.parentElement;

        gsap.set($el, { transformPerspective: PERSPECTIVE });

        this.rotateX = gsap.quickTo($el, 'rotationX', { duration: 0.6, ease: 'power3.out' });
        this.rotateY = gsap.quickTo($el, 'rotationY', { duration: 0.6, ease: 'power3.out' });

        this.$area.addEventListener('pointermove', (event) => this.onPointerMove(event));
        this.$area.addEventListener('pointerleave', () => this.reset());
    }

    onPointerMove(event) {
        if (event.pointerType === 'touch') {
            return;
        }

        const rect = this.$area.getBoundingClientRect();
        // -1 at the left/top edge, 1 at the right/bottom edge.
        const x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
        const y = ((event.clientY - rect.top) / rect.height) * 2 - 1;

        // Positive rotationY sends the right edge back; positive rotationX
        // sends the top edge back, hence the flipped sign for y.
        this.rotateY(x * MAX_TILT);
        this.rotateX(-y * MAX_TILT);
    }

    reset() {
        this.rotateX(0);
        this.rotateY(0);
    }
}
