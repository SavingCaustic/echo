// preData generated at build

const app = Vue.createApp({
    data() {
        return preData;
    },
    computed: {},
    methods: {
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
        calcRotarySwitchRotation(prop) {
            //simulate 5 steps. each step is 256/5 deg, and divide prop = 127/5
            const steps = (this.rotarySteps[prop] - 1);
            const rotationAngle = Math.round(Math.round(this[prop]/127*steps) * (256/steps) - 128);
            return {
                transform: `rotate(${rotationAngle}deg)`
            }
        },
        calcVsliderPosition(prop) {
            const stroke = (this.sliderLengths[prop]);
            const myProp = this[prop];
            const topOffset = stroke - Math.round(myProp/127 * stroke);
            return {
                top: topOffset + 'px'
            }
        },
        handleTap(event) {
            alert(event.target);
        },
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
        swypeBegin(event) {
            if (event.target.dataset.type == 'knob') {
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
            } else if(event.target.dataset.type == 'rotaryswitch') {
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
            } else if(event.target.dataset.type == 'vslider') {
                event.preventDefault();
                this.startX = event.touches ? event.touches[0].clientX : event.clientX;
                this.startY = event.touches ? event.touches[0].clientY : event.clientY;
                this.eventTarget = event.target.id;
                this.orgCC = this[this.eventTarget];
                this.rotating = true;   //refactor..??
                document.addEventListener('mousemove', this.swypeDo);
                document.addEventListener('mouseup', this.swypeEnd);
                document.addEventListener('touchmove', this.swypeDo);
                document.addEventListener('touchend', this.swypeEnd);                
            } else if(event.target.dataset.type == 'optbutton') {
                this.clickBegin(event);
            }
        },
        swypeDo(event) {
            //well here we should maybe pick up vslider?
            if (!this.rotating) return;

            const clientX = event.touches ? event.touches[0].clientX : event.clientX;
            const clientY = event.touches ? event.touches[0].clientY : event.clientY;
            const diffX = clientX - this.startX;
            const diffY = clientY - this.startY;
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
                    newCC = Math.round(this.orgCC - diffY / 1.6); //1.6 works best on vslider(L)
                    break;
                case 'X':
                    newCC = Math.round(this.orgCC + diffX / 10);
                    break;
            }
            if (newCC < 0) newCC = 0;
            if (newCC > 127) newCC = 127; 
            this.sendCC(this.eventTarget, newCC);
        },
        swypeEnd(event) {
            if (!this.rotating) return;

            document.removeEventListener('mousemove', this.swypeDo);
            document.removeEventListener('mouseup', this.swypeEnd);
            document.removeEventListener('touchmove', this.swypeDo);
            document.removeEventListener('touchend', this.swypeEnd);
            this.swypeAxis = '';
            this.rotating = false;
        },
        sendCC(target, value) {
            //should really bounce from backend
            //sending to WS later but now http. format message.
            const ccUrl = 'setVal.php?rack=1&module=synth&module_name=subreal&cc=' + target + '&val=' + value;
            //alert(ccUrl);
            //WS will ask player (or rack.json) which synth it is.
            //the synth has a json-file over its parameters with min, max and log-k.
            //the setVal will convert CC to synth val (0-5000) and possibly back to cc.
            this[target] = value;
            console.log(`Sending CC message: ${target} = ${value}`);
        }
    }
});

app.mount('#app');
