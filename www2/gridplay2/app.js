const app = Vue.createApp({
    //data, functions and templates
    //template: '<h2>I am the template</h2>' < template here is discouraged
    data() {
        return {
            isDragging: false,
            points: [
                {x:50, y:20, width:100, src:'1.png'},
                {x:100, y:60, width:40, src:'2.png'},
                {x:150, y:40, width:60, src:'2.png'}
            ]
        }
    },    
    methods: {
        dragStart(index, event) {
            this.isDragging = true;
            this.draggedIndex = index;
            this.startX = event.clientX; //x is mouse X, not object X
            this.startY = event.clientY;

        },
        handleDrag(index, event) {
            // Update the position of the dragged image
            let idx = this.draggedIndex;
            //not working - not clientX
            //this.points[idx].x = event.clientX;
            //this.points[idx].y = event.clientY;
        },
        dragEnd(index, event) {
            this.isDragging = false;
            endX = event.clientX;
            endY = event.clientY;
            this.points[index].x += (endX - this.startX);
            this.points[index].x = Math.floor(this.points[index].x / 8) * 8;  
        }
    }
})

app.mount('#app');