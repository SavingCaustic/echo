//preData generated at build

const app = Vue.createApp({
    data() {
        return preData;
    },
    computed: {
    },
    methods: {
        clickBegin(event) {
            //based on vue behavior, this should maybe just be called click?
            if (event.target.dataset.type == 'optbutton') {
                //is a enum value being shifted. if the FE knows count, it could just spin and send
                //value to backend. To avoid race, BE repiles and image changes.
                event.preventDefault();
                this.eventTarget = event.target.id;
                // Log the data-type attribute value
                this.orgCC = this[this.eventTarget];
                const dataCount = event.target.dataset.count;
                const newCC = (this.orgCC + 1) % dataCount;
                this.sendCC(this.eventTarget,newCC);
            }
        },
        calcOptButtonOffset(prop) {
            //86 is fake. it should really get the data-length from the tag.
            const leftOffset = this[prop] * 86 * -1;
            return {
                left: leftOffset + 'px'
            }
        },
        calcKnobRotation(prop) {
            const rotationAngle = this[prop] * 2 - 128;
            return {
                rotate: rotationAngle + 'deg'
            }
        },
        swypeBegin(event) {
            // Check if the event target is an image
            if (event.target.dataset.type === 'knob') {
                event.preventDefault();
                //this.startX = event.clientX || event.touches[0].clientX;
                //this.startY = event.clientY || event.touches[0].clientY;
                this.startX = event.touches ? event.touches[0].clientX : event.clientX;
                this.startY = event.touches ? event.touches[0].clientY : event.clientY;
                this.eventTarget = event.target.id;
                this.swypeAxis = '';
                this.orgCC = this[this.eventTarget];
                this.rotating = true;

                // Log the data-type attribute value
                const dataType = event.target.dataset.type;

                document.addEventListener('mousemove', this.swypeDo);
                document.addEventListener('mouseup', this.swypeEnd);
                document.addEventListener('touchmove', this.swypeDo);
                document.addEventListener('touchend', this.swypeEnd);
            }
        },

        swypeDo(event) {
            if (this.rotating) {
                //const diffX = (event.clientX || event.touches[0].clientX) - this.startX;
                const diffX = (event.touches ? event.touches[0].clientX : event.clientX) - this.startX;
                //const diffY = this.startY - (event.clientY || event.touches[0].clientY);
                const diffY = this.startY - (event.touches ? event.touches[0].clientY : event.clientY);
                //based on current data value and offset, calc new cc-value
                //we can't use this, provides funny results on long press: const currCC = this[this.eventTarget];
                this.vueLog = 'Mouse diff at (' + diffX + ',' + diffY + ')';
                let newCC = this.orgCC;
                switch(this.swypeAxis) {
                    case '':
                        //find out which axis to go.
                        if (Math.abs(diffY) > 3 && Math.abs(diffX) < 3) {
                            this.swypeAxis = 'Y';
                        }
                        if (Math.abs(diffX) > 3 && Math.abs(diffY) < 3) {
                            this.swypeAxis = 'X';
                        }
                        //continue
                    case 'Y':
                        newCC = Math.round(this.orgCC + diffY / 2);
                        break;
                    case 'X':
                        newCC = Math.round(this.orgCC + diffX / 10);
                        break;
                }
                if (newCC < 0) newCC = 0;
                if (newCC > 127) newCC = 127; 
                this.sendCC(this.eventTarget,newCC);
            }
        },
        sendCC(ccName, ccVal) {
            //really send this to web-socket server and let that server set the data anytime,
            //but fake now.
            //wsRequest('yada-yada'..)
            this[ccName] = ccVal;
        },
        swypeEnd(event) {
            this.rotating = false;
            document.removeEventListener('mousemove', this.swypeDo);
            document.removeEventListener('mouseup', this.swypeEnd);
            document.removeEventListener('touchmove', this.swypeDo);
            document.removeEventListener('touchend', this.swypeEnd);
        },
    }
});
const mountedApp = app.mount('#app');
