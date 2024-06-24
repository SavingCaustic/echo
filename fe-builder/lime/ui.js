// preData generated at build

const app = Vue.createApp({
    data() {
        return preData;
    },
    computed: {},
    methods: {
        clickBegin(event) {
            // Handle optbutton clicks
            if (event.target.dataset.type == 'optbutton') {
                event.preventDefault();
                this.eventTarget = event.target.id;
                const dataCount = event.target.dataset.count;
                const newCC = (this[this.eventTarget] + 1) % dataCount;
                this.sendCC(this.eventTarget, newCC);
            }
        },
        calcOptButtonOffset(prop) {
            const leftOffset = this[prop] * (-this.imgWidths[prop] - 6);
            return {
                left: leftOffset + 'px'
            }
        },
        calcKnobRotation(prop) {
            const rotationAngle = (this[prop] * 2) - 128;
            return {
                transform: `rotate(${rotationAngle}deg)`
            }
        },
        swypeBegin(event) {
            if (event.target.dataset.type === 'knob') {
                event.preventDefault();
                this.startX = event.touches ? event.touches[0].clientX : event.clientX;
                this.startY = event.touches ? event.touches[0].clientY : event.clientY;
                this.eventTarget = event.target.id;
                this.orgCC = this[this.eventTarget];
                this.rotating = true;

                document.addEventListener('mousemove', this.swypeDo);
                document.addEventListener('mouseup', this.swypeEnd);
                document.addEventListener('touchmove', this.swypeDo);
                document.addEventListener('touchend', this.swypeEnd);
            }
        },
        swypeDo(event) {
            if (!this.rotating) return;

            const clientX = event.touches ? event.touches[0].clientX : event.clientX;
            const clientY = event.touches ? event.touches[0].clientY : event.clientY;
            const deltaX = clientX - this.startX;
            const deltaY = clientY - this.startY;

            if (this.swypeAxis === '') {
                if (Math.abs(deltaX) > Math.abs(deltaY)) {
                    this.swypeAxis = 'x';
                } else {
                    this.swypeAxis = 'y';
                }
            }

            let newCC;
            if (this.swypeAxis === 'x') {
                newCC = Math.min(127, Math.max(0, this.orgCC + deltaX));
            } else {
                newCC = Math.min(127, Math.max(0, this.orgCC - deltaY));
            }

            this.sendCC(this.eventTarget, newCC);
        },
        swypeEnd(event) {
            if (!this.rotating) return;

            document.removeEventListener('mousemove', this.swypeDo);
            document.removeEventListener('mouseup', this.swypeEnd);
            document.removeEventListener('touchmove', this.swypeDo);
            document.removeEventListener('touchend', this.swypeEnd);

            this.rotating = false;
        },
        sendCC(target, value) {
            this[target] = value;
            // Update the knob rotation immediately
            this.$nextTick(() => {
                const knob = document.getElementById(target);
                if (knob && knob.dataset.type === 'knob') {
                    knob.style.transform = `rotate(${(value * 2) - 128}deg)`;
                }
            });
            // Simulate sending CC message to backend (replace with actual backend call)
            console.log(`Sending CC message: ${target} = ${value}`);
        }
    },
    mounted() {
        // Update initial knob positions
        this.$nextTick(() => {
            for (const prop in this.imgWidths) {
                const element = document.getElementById(prop);
                if (element && element.dataset.type === 'knob') {
                    element.style.transform = `rotate(${(this[prop] * 2) - 128}deg)`;
                }
            }
        });
    }
});

app.mount('#app');
