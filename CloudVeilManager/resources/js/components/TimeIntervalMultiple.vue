<script>
import slider from './Slider.vue'
export default {
    components: {
        slider
    },
    props: {
        formKey: String,
        maxIntervals: {
            type: String,
            default: 6
        },
        initialIntervals: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            intervals: JSON.parse(this.initialIntervals),
            debounceTimer: null,
            listHidden: true,
        }
    },
    created() {
        if(!this.intervals || this.intervals.length == 0) {
            this.intervals = [
                {
                    from: "00:00",
                    to: "23:59"
                }
            ];
        }
    },
    mounted() {
        this.refreshSlider();
    },
    methods: {
        addInterval() {
            this.intervals.push({
                from: "00:10",
                to: "00:30"
            });
            this.sortIntervals();
            this.refreshSlider();
        },
        sortIntervals() {
            this.intervals.sort((a, b) => a.from.localeCompare(b.from));
        },
        refreshSlider() {
            let sliderIntervals = [];
            this.intervals.forEach((interval) => {
                sliderIntervals.push(interval.from)
                sliderIntervals.push(interval.to)
            })
            this.$refs.slider.setIntervals(sliderIntervals);
        },
        onSliderChanged(values, handle) {
            let intervalIndex = Math.floor(handle / 2);
            if (intervalIndex < this.intervals.length) {
                this.intervals[intervalIndex].from = values[intervalIndex * 2];
                this.intervals[intervalIndex].to = values[intervalIndex * 2 + 1];
            } else {
                this.intervals.push({
                    from: values[intervalIndex * 2],
                    to: values[intervalIndex * 2 + 1]
                });
            }
        },
        removeInterval(index) {
            this.intervals.splice(index, 1);
            this.refreshSlider();
        },
        onTimeInput() {
            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
            }
            this.debounceTimer = setTimeout(this.refreshSlider, 100);
        }
    }
};
</script>

<template>
    <div>
        <slider class="slider" ref="slider" @updated="onSliderChanged" :initial-intervals="this.initialIntervals" />
    </div>
    <div style="z-index: 10">
        <div class="row">
            <a href="javascript:void(0)" class="col-md-2 pt-1" @click="this.listHidden = !this.listHidden">Toggle List</a>
        </div>
        <div v-show="!this.listHidden">
            <div class="row alert alert-info mt-2" v-if="intervals.length > 1">
                Only the very first interval would be used for older apps' versions.
            </div>
            <div class="row" v-for="(interval, index) in intervals">
                <div class="form-group col-md-2">
                    <label>From</label>
                    <input type="time" :name="formKey + '[' + index + '][from]'" v-model="interval.from" @change="onTimeInput" class="form-control">
                </div>
                <div class="form-group col-md-2">
                    <label>To</label>
                    <input type="time" :name="formKey + '[' + index + '][to]'" v-model="interval.to" @change="onTimeInput" class="form-control">
                </div>
                <div class="form-group col-md-2 d-flex flex-col" v-if="intervals.length > 1">
                    <a href="javascript:void(0)" @click="removeInterval(index)" class="btn btn-secondary mt-auto">Remove</a>
                </div>
            </div>
            <a href="javascript:void(0)" @click="addInterval" class="btn btn-secondary" v-if="intervals.length < maxIntervals">Add</a>
        </div>
    </div>
</template>
